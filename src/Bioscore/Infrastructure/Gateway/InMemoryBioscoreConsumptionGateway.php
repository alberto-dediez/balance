<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\Gateway;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionGateway;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptions;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
final class InMemoryBioscoreConsumptionGateway implements BioscoreConsumptionGateway
{
    /** @var array<string, BioscorePropertyConsumptions> */
    private array $entries = [];

    private ?int $failFromQuery = null;

    private int $attempts = 0;

    /** @var list<BioscoreConsumptionsQuery> */
    private array $queries = [];

    public function set(BioscorePropertyConsumptions $consumptions): void
    {
        $this->entries[(string) $consumptions->propertyId] = $consumptions;
    }

    public function add(PropertyId $propertyId, string $group, BioscoreConsumption $consumption, int $bioscoreId = 0, string $name = '', ?int $year = null): void
    {
        $current = $this->entries[(string) $propertyId] ?? new BioscorePropertyConsumptions(
            propertyId: $propertyId,
            bioscoreId: $bioscoreId,
            name: $name,
            year: $year ?? (int) date('Y'),
        );

        $electricity = $current->electricity;
        $combustibles = $current->combustibles;
        $refrigerantGases = $current->refrigerantGases;

        match ($group) {
            'electricity' => $electricity[] = $consumption,
            'combustibles' => $combustibles[] = $consumption,
            'refrigerant_gases' => $refrigerantGases[] = $consumption,
            default => throw new \InvalidArgumentException(sprintf('Unknown Bioscore consumption group "%s".', $group)),
        };

        $this->entries[(string) $propertyId] = new BioscorePropertyConsumptions(
            propertyId: $current->propertyId,
            bioscoreId: $current->bioscoreId,
            name: $current->name,
            year: $current->year,
            electricity: $electricity,
            combustibles: $combustibles,
            refrigerantGases: $refrigerantGases,
        );
    }

    public function fail(int $fromQuery = 1): void
    {
        $this->failFromQuery = $fromQuery;
    }

    public function reset(): void
    {
        $this->entries = [];
        $this->failFromQuery = null;
        $this->attempts = 0;
        $this->queries = [];
    }

    public function lastQuery(): ?BioscoreConsumptionsQuery
    {
        return $this->queries[array_key_last($this->queries) ?? -1] ?? null;
    }

    /** @return list<BioscoreConsumptionsQuery> */
    public function queries(): array
    {
        return $this->queries;
    }

    public function fetch(BioscoreConsumptionsQuery $query): array
    {
        ++$this->attempts;

        if (null !== $this->failFromQuery && $this->attempts >= $this->failFromQuery) {
            throw BioscoreUnavailableException::forConsumptions();
        }

        $this->queries[] = $query;

        $results = [];

        foreach ($query->propertyIds as $propertyId) {
            $entry = $this->entries[(string) $propertyId] ?? null;

            if (null === $entry) {
                continue;
            }

            $results[] = $entry;
        }

        return $results;
    }
}
