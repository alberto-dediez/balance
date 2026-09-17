<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Infrastructure\Gateway\BioscoreApiAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class BioscoreApiAuthenticatorTest extends TestCase
{
    /** @var list<string> */
    private array $requestedUrls = [];

    /** @var list<array<string, mixed>> */
    private array $requestedOptions = [];

    public function testLoginsOnceAndMemoizesTheToken(): void
    {
        $authenticator = $this->createSUT([$this->loginResponse('a-token')]);

        $this->assertSame('a-token', $authenticator->token());
        $this->assertSame('a-token', $authenticator->token());
        $this->assertCount(1, $this->requestedUrls);
        $this->assertSame('https://bioscore.test/api/login', $this->requestedUrls[0]);
        $this->assertSame('{"username":"events","password":"secret"}', $this->requestedOptions[0]['body']);
    }

    public function testInvalidatingForcesANewLogin(): void
    {
        $authenticator = $this->createSUT([$this->loginResponse('first'), $this->loginResponse('second')]);

        $authenticator->token();
        $authenticator->invalidate();

        $this->assertSame('second', $authenticator->token());
        $this->assertCount(2, $this->requestedUrls);
    }

    public function testAFailedLoginThrows(): void
    {
        $authenticator = $this->createSUT([new MockResponse('', ['http_code' => 401])]);

        $this->expectException(BioscoreUnavailableException::class);

        $authenticator->token();
    }

    public function testALoginWithoutTokenThrows(): void
    {
        $authenticator = $this->createSUT([$this->jsonResponse(['message' => 'ok'])]);

        $this->expectException(BioscoreUnavailableException::class);

        $authenticator->token();
    }

    /** @param list<MockResponse> $responses */
    private function createSUT(array $responses): BioscoreApiAuthenticator
    {
        $index = 0;

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($responses, &$index): MockResponse {
            $this->requestedUrls[] = $url;
            $this->requestedOptions[] = $options;

            return $responses[$index++];
        });

        return new BioscoreApiAuthenticator($httpClient, 'https://bioscore.test/api', 'events', 'secret');
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
