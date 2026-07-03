<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMobilityRepository implements MobilityRepository
{
    /** @param array<string, Mobility> $mobilities */
    public function __construct(
        private array $mobilities = [],
    ) {}

    public function add(Mobility $mobility): void
    {
        if (isset($this->mobilities[(string) $mobility->id()])) {
            return;
        }

        $this->mobilities[(string) $mobility->id()] = $mobility;
    }

    public function remove(Mobility $mobility): void
    {
        if (!isset($this->mobilities[(string) $mobility->id()])) {
            return;
        }

        unset($this->mobilities[(string) $mobility->id()]);
    }

    public function find(MobilityId $mobilityId): ?Mobility
    {
        return (isset($this->mobilities[(string) $mobilityId]) && false == $this->mobilities[(string) $mobilityId]->isRemoved())
            ? $this->mobilities[(string) $mobilityId]
            : null
        ;
    }

    /** @return array<int, Mobility> */
    public function findByFilter(MobilityFilter $filter): array
    {
        $results = [];

        if (0 === count($this->mobilities)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->mobilities as $mobility) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $mobility->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $mobility->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $mobility;
        }

        return $results;
    }

    public function totalResultsByFilter(MobilityFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
