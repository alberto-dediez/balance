<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionGateway;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptions;
use App\Property\Domain\Model\Property\PropertyId;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class HttpBioscoreConsumptionGateway implements BioscoreConsumptionGateway
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiBaseUrl,
        private readonly BioscoreApiAuthenticator $authenticator,
        private readonly LoggerInterface $logger,
    ) {}

    public function fetch(BioscoreConsumptionsQuery $query): array
    {
        $rows = $this->request($query, $this->authenticator->token());

        $results = [];

        foreach ($rows as $row) {
            $consumptions = $this->toPropertyConsumptions($row);

            if (null === $consumptions) {
                continue;
            }

            $results[] = $consumptions;
        }

        return $results;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws BioscoreUnavailableException
     */
    private function request(BioscoreConsumptionsQuery $query, string $token, bool $retry = true): array
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->apiBaseUrl, '/') . '/platform/consumptions', [
                'auth_bearer' => $token,
                'json' => [
                    'platform' => 'events',
                    'account_id' => (string) $query->accountId,
                    'property_ids' => array_map(static fn(PropertyId $propertyId): string => (string) $propertyId, $query->propertyIds),
                    'year' => $query->year,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (401 === $statusCode && $retry) {
                $this->authenticator->invalidate();

                return $this->request($query, $this->authenticator->token(), retry: false);
            }

            if (200 !== $statusCode) {
                throw BioscoreUnavailableException::forConsumptions();
            }

            return array_values($response->toArray());
        } catch (BioscoreUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw BioscoreUnavailableException::forConsumptions($e);
        }
    }

    /** @param array<string, mixed> $row */
    private function toPropertyConsumptions(array $row): ?BioscorePropertyConsumptions
    {
        $propertyId = $row['property_id'] ?? null;
        $bioscoreId = $row['bioscore_id'] ?? null;
        $name = $row['name'] ?? '';
        $year = $row['year'] ?? null;

        if (!is_string($propertyId) || !Uuid::isValid($propertyId) || !is_numeric($bioscoreId) || !is_numeric($year)) {
            $this->logger->warning('Skipping malformed Bioscore consumptions row', ['row' => $row]);

            return null;
        }

        return new BioscorePropertyConsumptions(
            propertyId: new PropertyId($propertyId),
            bioscoreId: (int) $bioscoreId,
            name: is_string($name) ? $name : '',
            year: (int) $year,
            electricity: $this->toConsumptions($row['electricity'] ?? []),
            combustibles: $this->toConsumptions($row['combustibles'] ?? []),
            refrigerantGases: $this->toConsumptions($row['refrigerant_gases'] ?? []),
        );
    }

    /** @return list<BioscoreConsumption> */
    private function toConsumptions(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $consumptions = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $consumption = $this->toConsumption($group);

            if (null === $consumption) {
                continue;
            }

            $consumptions[] = $consumption;
        }

        return $consumptions;
    }

    /** @param array<mixed> $group */
    private function toConsumption(array $group): ?BioscoreConsumption
    {
        $item = $group['item'] ?? null;
        $itemId = is_array($item) ? ($item['id'] ?? null) : null;
        $itemName = is_array($item) ? ($item['name'] ?? null) : null;
        $consumption = $group['consumption'] ?? null;
        $amountEur = $group['amount_eur'] ?? 0;

        if (!is_array($item) || !is_numeric($itemId) || !is_string($itemName) || '' === trim($itemName) || !is_numeric($consumption)) {
            $this->logger->warning('Skipping malformed Bioscore consumption group', ['group' => $group]);

            return null;
        }

        $unit = $item['unit'] ?? '';
        $usableForFacilities = $item['usable_for_facilities'] ?? null;
        $usableForTransportation = $item['usable_for_transportation'] ?? null;

        return new BioscoreConsumption(
            itemId: (int) $itemId,
            itemName: $itemName,
            itemUnit: is_string($unit) ? $unit : '',
            usableForFacilities: is_bool($usableForFacilities) ? $usableForFacilities : null,
            usableForTransportation: is_bool($usableForTransportation) ? $usableForTransportation : null,
            consumption: (float) $consumption,
            amountEur: is_numeric($amountEur) ? (float) $amountEur : 0.0,
        );
    }
}
