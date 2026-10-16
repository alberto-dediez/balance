<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEconomicSectorRepository implements EconomicSectorRepository
{
    /** @param array<string, EconomicSector> $economicSectors */
    public function __construct(
        private array $economicSectors = [],
    ) {}

    public function add(EconomicSector $economicSector): void
    {
        if (isset($this->economicSectors[(string) $economicSector->id()])) {
            return;
        }

        $this->economicSectors[(string) $economicSector->id()] = $economicSector;
    }

    public function remove(EconomicSector $economicSector): void
    {
        if (!isset($this->economicSectors[(string) $economicSector->id()])) {
            return;
        }

        unset($this->economicSectors[(string) $economicSector->id()]);
    }

    public function find(EconomicSectorId $economicSectorId): ?EconomicSector
    {
        return (isset($this->economicSectors[(string) $economicSectorId]) && false == $this->economicSectors[(string) $economicSectorId]->isRemoved())
            ? $this->economicSectors[(string) $economicSectorId]
            : null
        ;
    }

    public function findByClassificationCode(string $code): ?EconomicSector
    {
        foreach ($this->economicSectors as $economicSector) {
            if (!$economicSector->isRemoved() && $code === $economicSector->classificationCode()) {
                return $economicSector;
            }
        }

        return null;
    }

    /** @return array<int, EconomicSector> */
    public function findByFilter(EconomicSectorFilter $filter): array
    {
        $results = [];

        if (0 === count($this->economicSectors)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $economicSectors = array_values($this->economicSectors);
        usort(
            $economicSectors,
            static fn(EconomicSector $a, EconomicSector $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                'classificationCode' => $a->classificationCode() <=> $b->classificationCode(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($economicSectors as $economicSector) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $economicSector->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                !empty($filter->classificationCode)
                && !str_starts_with(mb_strtoupper((string) $economicSector->classificationCode()), mb_strtoupper(trim($filter->classificationCode)))
            ) {
                continue;
            }

            if (true == $economicSector->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $economicSector;
        }

        return $results;
    }

    public function totalResultsByFilter(EconomicSectorFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
