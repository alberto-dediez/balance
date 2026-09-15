<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryRefrigerantGasRepository implements RefrigerantGasRepository
{
    /** @param array<string, RefrigerantGas> $refrigerantGases */
    public function __construct(
        private array $refrigerantGases = [],
    ) {}

    public function add(RefrigerantGas $refrigerantGas): void
    {
        if (isset($this->refrigerantGases[(string) $refrigerantGas->id()])) {
            return;
        }

        $this->refrigerantGases[(string) $refrigerantGas->id()] = $refrigerantGas;
    }

    public function remove(RefrigerantGas $refrigerantGas): void
    {
        if (!isset($this->refrigerantGases[(string) $refrigerantGas->id()])) {
            return;
        }

        unset($this->refrigerantGases[(string) $refrigerantGas->id()]);
    }

    public function find(RefrigerantGasId $refrigerantGasId): ?RefrigerantGas
    {
        return (isset($this->refrigerantGases[(string) $refrigerantGasId]) && false == $this->refrigerantGases[(string) $refrigerantGasId]->isRemoved())
            ? $this->refrigerantGases[(string) $refrigerantGasId]
            : null
        ;
    }

    /** @return array<int, RefrigerantGas> */
    public function findByFilter(RefrigerantGasFilter $filter): array
    {
        $results = [];

        if (0 === count($this->refrigerantGases)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->refrigerantGases as $refrigerantGas) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $refrigerantGas->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $refrigerantGas->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $refrigerantGas;
        }

        return $results;
    }

    public function totalResultsByFilter(RefrigerantGasFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
