<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport\Erp;

use App\Shared\Infrastructure\ErrorReport\Erp\ErpErrorReportClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ErpErrorReportClientTest extends TestCase
{
    private const string API_TOKEN = 'a-secret-api-token';

    /** @var array<int,array{method: string, url: string, body: string, authorization: string, max_duration: mixed}> */
    private array $requests = [];

    public function testItLogsInAndReportsTheError(): void
    {
        $sut = $this->createSUT([$this->jwtResponse('a-jwt'), new MockResponse('{}', ['http_code' => 201])]);

        $sut->send(['fingerprint' => 'a-fingerprint']);

        $this->assertCount(2, $this->requests);
        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertSame('https://erp.test/api/login', $this->requests[0]['url']);
        $this->assertSame(
            ['username' => 'platform@email.com', 'password' => self::API_TOKEN],
            json_decode($this->requests[0]['body'], true),
        );
        $this->assertSame('POST', $this->requests[1]['method']);
        $this->assertSame('https://erp.test/api/crm/tickets/error-reports', $this->requests[1]['url']);
        $this->assertSame('Authorization: Bearer a-jwt', $this->requests[1]['authorization']);
        $this->assertSame(['fingerprint' => 'a-fingerprint'], json_decode($this->requests[1]['body'], true));
    }

    public function testItAcceptsAnAccumulatedOccurrence(): void
    {
        $sut = $this->createSUT([$this->jwtResponse('a-jwt'), new MockResponse('{}', ['http_code' => 200])]);

        $sut->send(['fingerprint' => 'a-fingerprint']);

        $this->assertCount(2, $this->requests);
    }

    public function testItReusesTheCachedJwt(): void
    {
        $sut = $this->createSUT([
            $this->jwtResponse('a-jwt'),
            new MockResponse('{}', ['http_code' => 201]),
            new MockResponse('{}', ['http_code' => 201]),
        ]);

        $sut->send(['fingerprint' => 'a-fingerprint']);
        $sut->send(['fingerprint' => 'another-fingerprint']);

        $this->assertCount(3, $this->requests);
        $this->assertSame('https://erp.test/api/crm/tickets/error-reports', $this->requests[2]['url']);
        $this->assertSame('Authorization: Bearer a-jwt', $this->requests[2]['authorization']);
    }

    public function testItDoesNotDuplicateTheSlashOfTheBaseUrl(): void
    {
        $sut = $this->createSUT([$this->jwtResponse('a-jwt'), new MockResponse('{}', ['http_code' => 201])], url: 'https://erp.test/');

        $sut->send(['fingerprint' => 'a-fingerprint']);

        $this->assertSame('https://erp.test/api/login', $this->requests[0]['url']);
        $this->assertSame('https://erp.test/api/crm/tickets/error-reports', $this->requests[1]['url']);
    }

    public function testItLogsInAgainWhenTheCachedJwtIsRejected(): void
    {
        $cache = new ArrayAdapter();

        $this->createSUT([$this->jwtResponse('an-old-jwt'), new MockResponse('{}', ['http_code' => 201])], cache: $cache)
            ->send(['fingerprint' => 'a-fingerprint']);

        $sut = $this->createSUT([
            new MockResponse('{}', ['http_code' => 401]),
            $this->jwtResponse('a-new-jwt'),
            new MockResponse('{}', ['http_code' => 201]),
        ], cache: $cache);

        $sut->send(['fingerprint' => 'a-fingerprint']);

        $this->assertCount(5, $this->requests);
        $this->assertSame('Authorization: Bearer an-old-jwt', $this->requests[2]['authorization']);
        $this->assertSame('https://erp.test/api/login', $this->requests[3]['url']);
        $this->assertSame('Authorization: Bearer a-new-jwt', $this->requests[4]['authorization']);
    }

    public function testEveryRequestIsLimitedToFiveSeconds(): void
    {
        $cache = new ArrayAdapter();

        $this->createSUT([$this->jwtResponse('an-old-jwt'), new MockResponse('{}', ['http_code' => 201])], cache: $cache)
            ->send(['fingerprint' => 'a-fingerprint']);

        $this->createSUT([
            new MockResponse('{}', ['http_code' => 401]),
            $this->jwtResponse('a-new-jwt'),
            new MockResponse('{}', ['http_code' => 201]),
        ], cache: $cache)->send(['fingerprint' => 'a-fingerprint']);

        $this->assertCount(5, $this->requests);

        foreach ($this->requests as $request) {
            $this->assertEquals(5, $request['max_duration']);
        }
    }

    public function testItFailsWhenTheRetryIsAlsoRejected(): void
    {
        $cache = new ArrayAdapter();

        $this->createSUT([$this->jwtResponse('an-old-jwt'), new MockResponse('{}', ['http_code' => 201])], cache: $cache)
            ->send(['fingerprint' => 'a-fingerprint']);

        $sut = $this->createSUT([
            new MockResponse('{}', ['http_code' => 401]),
            $this->jwtResponse('a-new-jwt'),
            new MockResponse('{}', ['http_code' => 401]),
        ], cache: $cache);

        try {
            $sut->send(['fingerprint' => 'a-fingerprint']);

            $this->fail('A \RuntimeException was expected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('401', $exception->getMessage());
        }

        $this->assertCount(5, $this->requests);
    }

    public function testItDoesNotRetryWhenAFreshJwtIsRejected(): void
    {
        $sut = $this->createSUT([$this->jwtResponse('a-jwt'), new MockResponse('{}', ['http_code' => 401])]);

        try {
            $sut->send(['fingerprint' => 'a-fingerprint']);

            $this->fail('A \RuntimeException was expected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('401', $exception->getMessage());
        }

        $this->assertCount(2, $this->requests);
    }

    public function testItFailsWithoutLeakingTheTokenWhenTheLoginIsRejected(): void
    {
        $sut = $this->createSUT([new MockResponse('{"message":"Invalid credentials."}', ['http_code' => 401])]);

        try {
            $sut->send(['fingerprint' => 'a-fingerprint']);

            $this->fail('A \RuntimeException was expected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('401', $exception->getMessage());
            $this->assertStringNotContainsString(self::API_TOKEN, $exception->getMessage());
        }

        $this->assertCount(1, $this->requests);
    }

    public function testItFailsWhenTheLoginDoesNotReturnAToken(): void
    {
        $sut = $this->createSUT([new MockResponse('{}', ['http_code' => 200])]);

        $this->expectException(\RuntimeException::class);

        $sut->send(['fingerprint' => 'a-fingerprint']);
    }

    /**
     * @return iterable<string,array{int}>
     */
    public static function failedStatusProvider(): iterable
    {
        yield 'unprocessable' => [422];
        yield 'server error' => [500];
    }

    /**
     * @dataProvider failedStatusProvider
     */
    public function testItFailsWhenTheReportIsRejected(int $status): void
    {
        $sut = $this->createSUT([$this->jwtResponse('a-jwt'), new MockResponse('{"error":"nope"}', ['http_code' => $status])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('status %d', $status));

        $sut->send(['fingerprint' => 'a-fingerprint']);
    }

    public function testItIsNotConfiguredWithoutUrlUsernameOrToken(): void
    {
        $this->assertTrue($this->createSUT([])->isConfigured());
        $this->assertFalse($this->createSUT([], url: '')->isConfigured());
        $this->assertFalse($this->createSUT([], username: ' ')->isConfigured());
        $this->assertFalse($this->createSUT([], apiToken: '')->isConfigured());
    }

    private function jwtResponse(string $jwt): MockResponse
    {
        return new MockResponse((string) json_encode(['token' => $jwt]), ['http_code' => 200]);
    }

    /**
     * @param MockResponse[] $responses
     */
    private function createSUT(
        array $responses,
        ?ArrayAdapter $cache = null,
        string $url = 'https://erp.test',
        string $username = 'platform@email.com',
        string $apiToken = self::API_TOKEN,
    ): ErpErrorReportClient {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => is_string($options['body'] ?? null) ? $options['body'] : '',
                'authorization' => $options['normalized_headers']['authorization'][0] ?? '',
                'max_duration' => $options['max_duration'] ?? null,
            ];

            $response = array_shift($responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        return new ErpErrorReportClient(
            httpClient: $httpClient,
            cache: $cache ?? new ArrayAdapter(),
            url: $url,
            username: $username,
            apiToken: $apiToken,
        );
    }
}
