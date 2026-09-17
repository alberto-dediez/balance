<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Infrastructure\Gateway\HttpBioscoreCatalogGateway;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpBioscoreCatalogGatewayTest extends TestCase
{
    /** @var list<string> */
    private array $requestedUrls = [];

    /** @var list<array<string, mixed>> */
    private array $requestedOptions = [];

    public function testLoginsOnceAndFetchesWithBearerToken(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('a-token'),
            new MockResponse(json_encode([
                ['id' => 1, 'name' => 'Gas natural', 'unit' => 'm3', 'usable_for_facilities' => true, 'usable_for_transportation' => false],
            ]) ?: '', ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]),
        ]);

        $entries = $gateway->fetch(BioscoreCatalog::FacilityFuel);

        $this->assertCount(1, $entries);
        $this->assertSame(1, $entries[0]->bioscoreId);
        $this->assertSame('Gas natural', $entries[0]->name);
        $this->assertSame('m3', $entries[0]->unit);
        $this->assertSame('https://bioscore.test/api/login', $this->requestedUrls[0]);
        $this->assertSame('https://bioscore.test/api/platform/combustibles', $this->requestedUrls[1]);
        $this->assertContains('Authorization: Bearer a-token', $this->requestedOptions[1]['headers']);
    }

    public function testSeparatesFacilityAndTransportFuelsByFlags(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Gas natural', 'unit' => 'm3', 'usable_for_facilities' => true, 'usable_for_transportation' => false],
            ['id' => 2, 'name' => 'Gasoil', 'unit' => 'l', 'usable_for_facilities' => true, 'usable_for_transportation' => true],
            ['id' => 3, 'name' => 'Gasolina', 'unit' => 'l', 'usable_for_facilities' => false, 'usable_for_transportation' => true],
        ];

        $facilityGateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse($rows)]);
        $transportGateway = $this->createSUT([$this->loginResponse('a-token'), $this->jsonResponse($rows)]);

        $facilityFuels = $facilityGateway->fetch(BioscoreCatalog::FacilityFuel);
        $transportFuels = $transportGateway->fetch(BioscoreCatalog::TransportFuel);

        $this->assertSame([1, 2], array_map(static fn($entry): int => $entry->bioscoreId, $facilityFuels));
        $this->assertSame([2, 3], array_map(static fn($entry): int => $entry->bioscoreId, $transportFuels));
    }

    public function testRetriesOnceAfterAnUnauthorizedResponse(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('expired-token'),
            new MockResponse('', ['http_code' => 401]),
            $this->loginResponse('fresh-token'),
            $this->jsonResponse([['id' => 9, 'name' => 'R-410A', 'unit' => 'kg']]),
        ]);

        $entries = $gateway->fetch(BioscoreCatalog::RefrigerantGas);

        $this->assertCount(1, $entries);
        $this->assertContains('Authorization: Bearer fresh-token', $this->requestedOptions[3]['headers']);
    }

    public function testServerErrorThrows(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('a-token'),
            new MockResponse('', ['http_code' => 500]),
        ]);

        $this->expectException(BioscoreUnavailableException::class);

        $gateway->fetch(BioscoreCatalog::ElectricityMarketer);
    }

    public function testFailedLoginThrows(): void
    {
        $gateway = $this->createSUT([new MockResponse('', ['http_code' => 401])]);

        $this->expectException(BioscoreUnavailableException::class);

        $gateway->fetch(BioscoreCatalog::ElectricityMarketer);
    }

    public function testRowWithoutIdIsSkipped(): void
    {
        $gateway = $this->createSUT([
            $this->loginResponse('a-token'),
            $this->jsonResponse([
                ['name' => 'Sin identificador', 'unit' => 'kWh'],
                ['id' => 4, 'name' => 'Endesa', 'unit' => 'kWh'],
            ]),
        ]);

        $entries = $gateway->fetch(BioscoreCatalog::ElectricityMarketer);

        $this->assertCount(1, $entries);
        $this->assertSame('Endesa', $entries[0]->name);
    }

    /** @param list<MockResponse> $responses */
    private function createSUT(array $responses): HttpBioscoreCatalogGateway
    {
        return new HttpBioscoreCatalogGateway(
            httpClient: $this->httpClient($responses),
            apiBaseUrl: 'https://bioscore.test/api',
            username: 'events',
            password: 'secret',
            logger: new NullLogger(),
        );
    }

    /** @param list<MockResponse> $responses */
    private function httpClient(array $responses): HttpClientInterface
    {
        $index = 0;

        return new MockHttpClient(function (string $method, string $url, array $options) use ($responses, &$index): MockResponse {
            $this->requestedUrls[] = $url;
            $this->requestedOptions[] = $options;

            return $responses[$index++];
        });
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
