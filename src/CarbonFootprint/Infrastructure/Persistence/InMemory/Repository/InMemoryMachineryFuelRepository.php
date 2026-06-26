<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMachineryFuelRepository implements MachineryFuelRepository
{
    /** @param array<string, MachineryFuel> $machineryFuels */
    public function __construct(
        private array $machineryFuels = [],
    ) {}

    public function add(MachineryFuel $machineryFuel): void
    {
        if (isset($this->machineryFuels[(string) $machineryFuel->id()])) {
            return;
        }

        $this->machineryFuels[(string) $machineryFuel->id()] = $machineryFuel;
    }

    public function remove(MachineryFuel $machineryFuel): void
    {
        if (!isset($this->machineryFuels[(string) $machineryFuel->id()])) {
            return;
        }

        unset($this->machineryFuels[(string) $machineryFuel->id()]);
    }

    public function find(MachineryFuelId $machineryFuelId): ?MachineryFuel
    {
        return (isset($this->machineryFuels[(string) $machineryFuelId]) && false == $this->machineryFuels[(string) $machineryFuelId]->isRemoved())
            ? $this->machineryFuels[(string) $machineryFuelId]
            : null
        ;
    }

    public function findOneByName(string $name): ?MachineryFuel
    {
        foreach ($this->machineryFuels as $machineryFuel) {
            if (true == $machineryFuel->isRemoved()) {
                continue;
            }

            if (strtolower(trim((string) $machineryFuel->name())) === strtolower(trim($name))) {
                return $machineryFuel;
            }
        }

        return null;
    }

    /** @return array<int, MachineryFuel> */
    public function findByFilter(MachineryFuelFilter $filter): array
    {
        $results = [];

        if (0 === count($this->machineryFuels)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->machineryFuels as $machineryFuel) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $machineryFuel->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->machineryType && $machineryFuel->machineryType() !== $filter->machineryType) {
                continue;
            }

            if (true == $machineryFuel->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $machineryFuel;
        }

        return $results;
    }

    public function totalResultsByFilter(MachineryFuelFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
