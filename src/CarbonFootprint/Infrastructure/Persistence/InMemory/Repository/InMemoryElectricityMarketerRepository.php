<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryElectricityMarketerRepository implements ElectricityMarketerRepository
{
    /** @param array<string, ElectricityMarketer> $electricityMarketers */
    public function __construct(
        private array $electricityMarketers = [],
    ) {}

    public function add(ElectricityMarketer $electricityMarketer): void
    {
        if (isset($this->electricityMarketers[(string) $electricityMarketer->id()])) {
            return;
        }

        $this->electricityMarketers[(string) $electricityMarketer->id()] = $electricityMarketer;
    }

    public function remove(ElectricityMarketer $electricityMarketer): void
    {
        if (!isset($this->electricityMarketers[(string) $electricityMarketer->id()])) {
            return;
        }

        unset($this->electricityMarketers[(string) $electricityMarketer->id()]);
    }

    public function find(ElectricityMarketerId $electricityMarketerId): ?ElectricityMarketer
    {
        return (isset($this->electricityMarketers[(string) $electricityMarketerId]) && false == $this->electricityMarketers[(string) $electricityMarketerId]->isRemoved())
            ? $this->electricityMarketers[(string) $electricityMarketerId]
            : null
        ;
    }

    public function findOneByName(string $name): ?ElectricityMarketer
    {
        foreach ($this->electricityMarketers as $electricityMarketer) {
            if (true == $electricityMarketer->isRemoved()) {
                continue;
            }

            if (strtolower(trim((string) $electricityMarketer->name())) === strtolower(trim($name))) {
                return $electricityMarketer;
            }
        }

        return null;
    }

    /** @return array<int, ElectricityMarketer> */
    public function findByFilter(ElectricityMarketerFilter $filter): array
    {
        $results = [];

        if (0 === count($this->electricityMarketers)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->electricityMarketers as $electricityMarketer) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $electricityMarketer->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $electricityMarketer->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $electricityMarketer;
        }

        return $results;
    }

    public function totalResultsByFilter(ElectricityMarketerFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
