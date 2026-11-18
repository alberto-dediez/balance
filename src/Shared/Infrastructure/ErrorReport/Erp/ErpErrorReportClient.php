<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport\Erp;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ErpErrorReportClient
{
    private const string LOGIN_PATH = '/api/login';

    private const string REPORT_PATH = '/api/crm/tickets/error-reports';

    private const int TIMEOUT = 5;

    private const int MAX_DURATION = 5;

    private const int JWT_TTL = 600;

    private const int MAX_ERROR_BODY_LENGTH = 500;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $url,
        private readonly string $username,
        #[\SensitiveParameter]
        private readonly string $apiToken,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== trim($this->url) && '' !== trim($this->username) && '' !== trim($this->apiToken);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function send(array $payload): void
    {
        $jwt = $this->cachedJwt();
        $fromCache = null !== $jwt;

        $response = $this->report($jwt ?? $this->freshJwt(), $payload);

        if (401 === $response->getStatusCode() && $fromCache) {
            $this->forgetJwt();

            $response = $this->report($this->freshJwt(), $payload);
        }

        $status = $response->getStatusCode();

        if (200 !== $status && 201 !== $status) {
            throw new \RuntimeException(sprintf('ERP error report failed with status %d: %s', $status, mb_substr($response->getContent(false), 0, self::MAX_ERROR_BODY_LENGTH)));
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function report(string $jwt, array $payload): ResponseInterface
    {
        return $this->httpClient->request('POST', $this->endpoint(self::REPORT_PATH), [
            ...$this->options(),
            'auth_bearer' => $jwt,
            'json' => $payload,
        ]);
    }

    private function login(): string
    {
        $response = $this->httpClient->request('POST', $this->endpoint(self::LOGIN_PATH), [
            ...$this->options(),
            'json' => ['username' => $this->username, 'password' => $this->apiToken],
        ]);

        $status = $response->getStatusCode();
        $token = 200 === $status ? ($response->toArray(false)['token'] ?? null) : null;

        if (!is_string($token) || '' === $token) {
            throw new \RuntimeException(sprintf('ERP login failed with status %d', $status));
        }

        return $token;
    }

    private function cachedJwt(): ?string
    {
        try {
            $item = $this->cache->getItem($this->cacheKey());

            if (!$item->isHit()) {
                return null;
            }

            $jwt = $item->get();
        } catch (\Throwable) {
            return null;
        }

        return is_string($jwt) && '' !== $jwt ? $jwt : null;
    }

    private function freshJwt(): string
    {
        $jwt = $this->login();

        try {
            $item = $this->cache->getItem($this->cacheKey());
            $item->set($jwt);
            $item->expiresAfter(self::JWT_TTL);

            $this->cache->save($item);
        } catch (\Throwable) {
        }

        return $jwt;
    }

    private function forgetJwt(): void
    {
        try {
            $this->cache->deleteItem($this->cacheKey());
        } catch (\Throwable) {
        }
    }

    private function cacheKey(): string
    {
        return sprintf('error_report_erp_jwt_%s', sha1(sprintf('%s|%s|%s', $this->url, $this->username, $this->apiToken)));
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->url, '/') . $path;
    }

    /**
     * @return array<string,mixed>
     */
    private function options(): array
    {
        return [
            'timeout' => self::TIMEOUT,
            'max_duration' => self::MAX_DURATION,
            'headers' => ['Accept' => 'application/json'],
        ];
    }
}
