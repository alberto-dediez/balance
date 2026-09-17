<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogEntry;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogGateway;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class HttpBioscoreCatalogGateway implements BioscoreCatalogGateway
{
    private ?string $token = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiBaseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetch(BioscoreCatalog $catalog): array
    {
        $rows = $this->request($catalog, $this->token());

        $entries = [];

        foreach ($rows as $row) {
            $entry = $this->toEntry($catalog, $row);

            if (null === $entry) {
                continue;
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws BioscoreUnavailableException
     */
    private function request(BioscoreCatalog $catalog, string $token, bool $retry = true): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->url($catalog), ['auth_bearer' => $token]);

            $statusCode = $response->getStatusCode();

            if (401 === $statusCode && $retry) {
                $this->token = null;

                return $this->request($catalog, $this->token(), retry: false);
            }

            if (200 !== $statusCode) {
                throw BioscoreUnavailableException::fromCatalog($catalog);
            }

            return array_values($response->toArray());
        } catch (BioscoreUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw BioscoreUnavailableException::fromCatalog($catalog, $e);
        }
    }

    private function url(BioscoreCatalog $catalog): string
    {
        $path = match ($catalog) {
            BioscoreCatalog::FacilityFuel, BioscoreCatalog::TransportFuel => '/platform/combustibles',
            BioscoreCatalog::RefrigerantGas => '/platform/refrigerant-gases',
            BioscoreCatalog::ElectricityMarketer => '/platform/electricity-marketers',
        };

        return rtrim($this->apiBaseUrl, '/') . $path;
    }

    /** @param array<string, mixed> $row */
    private function toEntry(BioscoreCatalog $catalog, array $row): ?BioscoreCatalogEntry
    {
        $bioscoreId = $row['id'] ?? null;
        $name = $row['name'] ?? null;

        if (!is_numeric($bioscoreId) || !is_string($name) || '' === trim($name)) {
            $this->logger->warning('Skipping malformed Bioscore catalog row', ['catalog' => $catalog->value, 'row' => $row]);

            return null;
        }

        if (BioscoreCatalog::FacilityFuel === $catalog && true !== ($row['usable_for_facilities'] ?? null)) {
            return null;
        }

        if (BioscoreCatalog::TransportFuel === $catalog && true !== ($row['usable_for_transportation'] ?? null)) {
            return null;
        }

        $unit = $row['unit'] ?? '';

        return new BioscoreCatalogEntry(
            bioscoreId: (int) $bioscoreId,
            name: $name,
            unit: is_string($unit) ? $unit : '',
        );
    }

    private function token(): string
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
}
