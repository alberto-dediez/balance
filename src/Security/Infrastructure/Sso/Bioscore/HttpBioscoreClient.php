<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore;

use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class HttpBioscoreClient implements BioscoreClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $ssoUrl,
        private string $apiBaseUrl,
        private string $origin,
    ) {}

    public function loginUrl(Locale $locale): string
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
