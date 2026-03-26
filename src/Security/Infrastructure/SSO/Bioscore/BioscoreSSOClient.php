<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\SSO\Bioscore;

use App\Security\Infrastructure\SSO\SSOClient;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
class BioscoreSSOClient implements SSOClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ssoUrl,
        private readonly string $apiBaseUrl,
        private readonly string $origin,
    ) {}

    public function getLoginUrl(Locale $locale = Locale::Spanish): string
    {
        return sprintf(
            '%s/%s/login-sso/%s',
            rtrim($this->ssoUrl, '/'),
            $locale->value,
            $this->origin,
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
