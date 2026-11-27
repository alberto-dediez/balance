<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Storage\GoogleDrive;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleDriveAccessTokenProvider
{
    private const string SCOPE = 'https://www.googleapis.com/auth/drive';

    private const string GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    private const int JWT_TTL = 3600;

    private const int EXPIRATION_MARGIN = 60;

    private const string NOT_CONFIGURED = 'Google Drive storage is enabled but GOOGLE_DRIVE_* variables are not configured';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $credentialsFile,
    ) {}

    public function token(): string
    {
        $credentials = $this->credentials();

        $item = $this->cache->getItem('google_drive.access_token.' . sha1($credentials['client_email']));

        if ($item->isHit()) {
            $token = $item->get();

            if (is_string($token) && '' !== $token) {
                return $token;
            }
        }

        [$token, $expiresIn] = $this->request($credentials['token_uri'], $this->jwt($credentials));

        $item->set($token);
        $item->expiresAfter(max(self::EXPIRATION_MARGIN, $expiresIn - self::EXPIRATION_MARGIN));

        $this->cache->save($item);

        return $token;
    }

    /**
     * @return array{client_email: string, private_key: string, token_uri: string}
     */
    private function credentials(): array
    {
        if ('' === $this->credentialsFile || !is_file($this->credentialsFile) || !is_readable($this->credentialsFile)) {
            throw new \InvalidArgumentException(self::NOT_CONFIGURED);
        }

        $credentials = json_decode((string) file_get_contents($this->credentialsFile), true);

        if (!is_array($credentials)) {
            throw new \InvalidArgumentException(self::NOT_CONFIGURED);
        }

        foreach (['client_email', 'private_key', 'token_uri'] as $key) {
            if (!isset($credentials[$key]) || !is_string($credentials[$key]) || '' === $credentials[$key]) {
                throw new \InvalidArgumentException(self::NOT_CONFIGURED);
            }
        }

        return [
            'client_email' => $credentials['client_email'],
            'private_key' => $credentials['private_key'],
            'token_uri' => $credentials['token_uri'],
        ];
    }

    /**
     * @param array{client_email: string, private_key: string, token_uri: string} $credentials
     */
    private function jwt(array $credentials): string
    {
        $now = time();

        $unsigned = self::base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
            . '.'
            . self::base64Url((string) json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + self::JWT_TTL,
            ]));

        $signature = '';

        if (!@openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \InvalidArgumentException(self::NOT_CONFIGURED);
        }

        return $unsigned . '.' . self::base64Url($signature);
    }

    /**
     * @return array{string, int}
     */
    private function request(string $tokenUri, string $jwt): array
    {
        try {
            $response = $this->httpClient->request('POST', $tokenUri, [
                'body' => ['grant_type' => self::GRANT_TYPE, 'assertion' => $jwt],
            ]);

            $status = $response->getStatusCode();
            $data = 200 === $status ? $response->toArray(false) : [];
        } catch (ExceptionInterface $exception) {
            throw new GoogleDriveException('Unable to obtain a Google Drive access token', 0, $exception);
        }

        $token = $data['access_token'] ?? null;

        if (!is_string($token) || '' === $token) {
            throw new GoogleDriveException('Unable to obtain a Google Drive access token');
        }

        $expiresIn = $data['expires_in'] ?? self::JWT_TTL;

        return [$token, is_int($expiresIn) ? $expiresIn : self::JWT_TTL];
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
