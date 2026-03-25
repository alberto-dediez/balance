<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Bioscore;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BioscoreSsoClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ssoUrl,
        private readonly string $apiBaseUrl,
        private readonly string $platform,
    ) {}

    public function getLoginUrl(string $language = 'es'): string
    {
        return sprintf(
            '%s/%s/login-sso/%s',
            rtrim($this->ssoUrl, '/'),
            $language,
            $this->platform,
        );
    }

    public function checkToken(string $token): bool
    {
        try {
            $response = $this->httpClient->request('POST', sprintf('%s/sso-token/check', rtrim($this->apiBaseUrl, '/')), [
                'json' => ['token' => $token],
            ]);

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            $data = $response->toArray();

            return true === ($data['valid'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }
}
