<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Bioscore\Infrastructure\Gateway\BioscoreApiAuthenticator;
use App\Bioscore\Infrastructure\Gateway\HttpBioscoreConsumptionGateway;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class HttpBioscoreConsumptionGatewayTest extends TestCase
{
    private const PROPERTY_ID = '018f2b3e-6d5a-7c1b-9e0f-1a2b3c4d5e6f';

    /** @var list<string> */
    private array $requestedMethods = [];

    /** @var list<string> */
    private array $requestedUrls = [];

    /** @var list<array<string, mixed>> */
    private array $requestedOptions = [];

    public function testSendsTheQueryAsJsonBodyWithBearerToken(): void
    {
        $gateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse([])]);

        $results = $gateway->fetch($this->query());

        $this->assertSame([], $results);
        $this->assertSame('GET', $this->requestedMethods[1]);
        $this->assertSame('https://bioscore.test/api/platform/consumptions', $this->requestedUrls[1]);
        $this->assertContains('Authorization: Bearer a-token', $this->requestedOptions[1]['headers']);
        $this->assertContains('Content-Type: application/json', $this->requestedOptions[1]['headers']);
        $this->assertSame(
            ['platform' => 'events', 'account_id' => 'account-id', 'property_ids' => [self::PROPERTY_ID], 'year' => 2025],
            json_decode((string) $this->requestedOptions[1]['body'], true),
        );
    }

    public function testMapsEveryConsumptionGroup(): void
    {
        $gateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse([[
            'property_id' => self::PROPERTY_ID,
            'bioscore_id' => 42,
            'name' => 'Hotel',
            'year' => 2025,
            'electricity' => [
                ['item' => ['id' => 3, 'name' => 'Endesa', 'unit' => 'kWh', 'usable_for_facilities' => null, 'usable_for_transportation' => null], 'consumption' => 10000.5, 'amount_eur' => 1500.25, 'invoices' => []],
            ],
            'combustibles' => [
                ['item' => ['id' => 7, 'name' => 'Gas natural', 'unit' => 'm3', 'usable_for_facilities' => true, 'usable_for_transportation' => false], 'consumption' => 1000, 'amount_eur' => 0],
                ['item' => ['id' => 8, 'name' => 'Gasoil', 'unit' => '', 'usable_for_facilities' => true, 'usable_for_transportation' => true], 'consumption' => '500', 'amount_eur' => '12.5'],
            ],
            'refrigerant_gases' => [
                ['item' => ['id' => 9, 'name' => 'R-410A', 'unit' => 'kg'], 'consumption' => 3],
            ],
            'occupation' => null,
        ]])]);

        $results = $gateway->fetch($this->query());

        $this->assertCount(1, $results);
        $property = $results[0];
        $this->assertSame(self::PROPERTY_ID, (string) $property->propertyId);
        $this->assertSame(42, $property->bioscoreId);
        $this->assertSame('Hotel', $property->name);
        $this->assertSame(2025, $property->year);

        $this->assertCount(1, $property->electricity);
        $this->assertSame(3, $property->electricity[0]->itemId);
        $this->assertSame('kWh', $property->electricity[0]->itemUnit);
        $this->assertNull($property->electricity[0]->usableForFacilities);
        $this->assertSame(10000.5, $property->electricity[0]->consumption);
        $this->assertSame(1500.25, $property->electricity[0]->amountEur);

        $this->assertCount(2, $property->combustibles);
        $this->assertTrue($property->combustibles[0]->usableForFacilities);
        $this->assertFalse($property->combustibles[0]->usableForTransportation);
        $this->assertSame('', $property->combustibles[1]->itemUnit);
        $this->assertTrue($property->combustibles[1]->usableForTransportation);
        $this->assertSame(500.0, $property->combustibles[1]->consumption);

        $this->assertCount(1, $property->refrigerantGases);
        $this->assertSame('R-410A', $property->refrigerantGases[0]->itemName);
        $this->assertSame(0.0, $property->refrigerantGases[0]->amountEur);
    }

    public function testRowsWithoutAValidPropertyIdAreSkipped(): void
    {
        $gateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse([
            ['property_id' => 'not-a-uuid', 'bioscore_id' => 1, 'name' => 'Hotel', 'year' => 2025, 'electricity' => [], 'combustibles' => [], 'refrigerant_gases' => []],
            ['property_id' => self::PROPERTY_ID, 'bioscore_id' => 2, 'name' => 'Hotel', 'year' => 2025, 'electricity' => [], 'combustibles' => [], 'refrigerant_gases' => []],
        ])]);

        $results = $gateway->fetch($this->query());

        $this->assertCount(1, $results);
        $this->assertSame(2, $results[0]->bioscoreId);
    }

    public function testGroupsWithoutItemIdAreSkipped(): void
    {
        $gateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse([[
            'property_id' => self::PROPERTY_ID,
            'bioscore_id' => 1,
            'name' => 'Hotel',
            'year' => 2025,
            'electricity' => [],
            'combustibles' => [
                ['item' => ['name' => 'Sin identificador', 'unit' => 'l'], 'consumption' => 10],
                ['item' => ['id' => 8, 'name' => 'Gasoil', 'unit' => 'l'], 'consumption' => 20],
            ],
            'refrigerant_gases' => [],
        ]])]);

        $results = $gateway->fetch($this->query());

        $this->assertCount(1, $results[0]->combustibles);
        $this->assertSame(8, $results[0]->combustibles[0]->itemId);
    }

    public function testRetriesOnceAfterAnUnauthorizedResponse(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('expired-token'),
            new MockResponse('', ['http_code' => 401]),
            $this->loginResponse('fresh-token'),
            $this->jsonResponse([]),
        ]);

        $gateway->fetch($this->query());

        $this->assertCount(4, $this->requestedUrls);
        $this->assertContains('Authorization: Bearer fresh-token', $this->requestedOptions[3]['headers']);
    }

    public function testServerErrorThrows(): void
    {
        $gateway = $this->createSUT([$this->loginResponse('a-token'), new MockResponse('', ['http_code' => 500])]);

        $this->expectException(BioscoreUnavailableException::class);

        $gateway->fetch($this->query());
    }

    public function testInvalidJsonThrows(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('a-token'),
            new MockResponse('not json', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $this->expectException(BioscoreUnavailableException::class);

        $gateway->fetch($this->query());
    }

    private function query(): BioscoreConsumptionsQuery
    {
        return new BioscoreConsumptionsQuery(new AccountId('account-id'), [new PropertyId(self::PROPERTY_ID)], 2025);
    }

    /** @param list<MockResponse> $responses */
    private function createSUT(array $responses): HttpBioscoreConsumptionGateway
    {
        $index = 0;

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($responses, &$index): MockResponse {
            $this->requestedMethods[] = $method;
            $this->requestedUrls[] = $url;
            $this->requestedOptions[] = $options;

            return $responses[$index++];
        });

        return new HttpBioscoreConsumptionGateway(
            httpClient: $httpClient,
            apiBaseUrl: 'https://bioscore.test/api',
            authenticator: new BioscoreApiAuthenticator($httpClient, 'https://bioscore.test/api', 'events', 'secret'),
            logger: new NullLogger(),
        );
    }

    private function loginResponse(string $token): MockResponse
    {
        return $this->jsonResponse(['token' => $token]);
    }

    /** @param array<mixed> $payload */
    private function jsonResponse(array $payload): MockResponse
    {
        return new MockResponse(
            json_encode($payload) ?: '',
            ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']],
        );
    }
}
