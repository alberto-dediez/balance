<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Storage\GoogleDrive;

use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveAccessTokenProvider;
use App\Shared\Infrastructure\Storage\GoogleDrive\GoogleDriveException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleDriveAccessTokenProviderTest extends TestCase
{
    /**
     * @var list<array{method: string, url: string, body: string}>
     */
    private array $requests = [];

    /**
     * @var list<MockResponse>
     */
    private array $responses = [];

    protected function setUp(): void
    {
        $this->requests = [];
        $this->responses = [];
    }

    public function testToken(): void
    {
        $this->responses[] = new MockResponse((string) json_encode(['access_token' => 'token-1', 'expires_in' => 3599, 'token_type' => 'Bearer']), ['http_code' => 200]);

        $before = time();

        $token = $this->createSUT()->token();

        $this->assertSame('token-1', $token);
        $this->assertCount(1, $this->requests);
        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertSame(GoogleDriveMother::TOKEN_URI, $this->requests[0]['url']);

        parse_str($this->requests[0]['body'], $form);

        $this->assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $form['grant_type'] ?? null);
        $this->assertIsString($form['assertion'] ?? null);

        $parts = explode('.', $form['assertion']);

        $this->assertCount(3, $parts);
        $this->assertSame(['alg' => 'RS256', 'typ' => 'JWT'], $this->decode($parts[0]));

        $claims = $this->decode($parts[1]);

        $this->assertSame(GoogleDriveMother::CLIENT_EMAIL, $claims['iss']);
        $this->assertSame('https://www.googleapis.com/auth/drive', $claims['scope']);
        $this->assertSame(GoogleDriveMother::TOKEN_URI, $claims['aud']);
        $this->assertIsInt($claims['iat']);
        $this->assertGreaterThanOrEqual($before, $claims['iat']);
        $this->assertSame($claims['iat'] + 3600, $claims['exp']);

        $signature = base64_decode(strtr($parts[2], '-_', '+/'), true);

        $this->assertIsString($signature);
        $this->assertSame(1, openssl_verify($parts[0] . '.' . $parts[1], $signature, GoogleDriveMother::publicKey(), OPENSSL_ALGO_SHA256));
    }

    public function testTokenIsCached(): void
    {
        $this->responses[] = new MockResponse((string) json_encode(['access_token' => 'token-1', 'expires_in' => 3599]), ['http_code' => 200]);

        $sut = $this->createSUT();

        $this->assertSame('token-1', $sut->token());
        $this->assertSame('token-1', $sut->token());
        $this->assertCount(1, $this->requests);
    }

    /**
     * @dataProvider invalidCredentialsFiles
     */
    public function testInvalidCredentials(string $credentialsFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GOOGLE_DRIVE_*');

        try {
            $this->createSUT($credentialsFile)->token();
        } finally {
            $this->assertCount(0, $this->requests);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCredentialsFiles(): iterable
    {
        $credentials = GoogleDriveMother::credentials();
        unset($credentials['private_key']);

        yield 'empty' => [''];
        yield 'missing file' => [sys_get_temp_dir() . '/google-drive-missing-' . bin2hex(random_bytes(4)) . '.json'];
        yield 'not json' => [GoogleDriveMother::file('not-json')];
        yield 'without private key' => [GoogleDriveMother::credentialsFile($credentials)];
    }

    public function testInvalidPrivateKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('GOOGLE_DRIVE_*');

        $this->createSUT(GoogleDriveMother::credentialsFile([...GoogleDriveMother::credentials(), 'private_key' => 'not-a-key']))->token();
    }

    public function testRejectedRequest(): void
    {
        $this->expectException(GoogleDriveException::class);
        $this->expectExceptionMessage('Unable to obtain a Google Drive access token');

        $this->responses[] = new MockResponse((string) json_encode(['error' => 'invalid_grant']), ['http_code' => 400]);

        $this->createSUT()->token();
    }

    public function testNetworkFailure(): void
    {
        $this->expectException(GoogleDriveException::class);

        $this->responses[] = new MockResponse('', ['error' => 'Connection refused']);

        $this->createSUT()->token();
    }

    private function createSUT(?string $credentialsFile = null): GoogleDriveAccessTokenProvider
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => is_string($options['body'] ?? null) ? $options['body'] : '',
            ];

            $response = array_shift($this->responses);

            $this->assertInstanceOf(MockResponse::class, $response);

            return $response;
        });

        return new GoogleDriveAccessTokenProvider($httpClient, new ArrayAdapter(), $credentialsFile ?? GoogleDriveMother::credentialsFile());
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $part): array
    {
        $decoded = json_decode((string) base64_decode(strtr($part, '-_', '+/')), true);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
