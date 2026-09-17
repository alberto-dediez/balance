<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class BioscoreApiAuthenticator
{
    private ?string $token = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiBaseUrl,
        private readonly string $username,
        private readonly string $password,
    ) {}

    /** @throws BioscoreUnavailableException */
    public function token(): string
    {
        if (null !== $this->token) {
            return $this->token;
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->apiBaseUrl, '/') . '/login', [
                'json' => ['username' => $this->username, 'password' => $this->password],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw BioscoreUnavailableException::authenticationFailed();
            }

            $token = $response->toArray()['token'] ?? null;

            if (!is_string($token) || '' === $token) {
                throw BioscoreUnavailableException::authenticationFailed();
            }

            return $this->token = $token;
        } catch (BioscoreUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw BioscoreUnavailableException::authenticationFailed($e);
        }
    }

    public function invalidate(): void
    {
        $this->token = null;
    }
}
