<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryRoadTransportRepository implements RoadTransportRepository
{
    /** @param array<string, RoadTransport> $roadTransports */
    public function __construct(
        private array $roadTransports = [],
    ) {}

    public function add(RoadTransport $roadTransport): void
    {
        if (isset($this->roadTransports[(string) $roadTransport->id()])) {
            return;
        }

        $this->roadTransports[(string) $roadTransport->id()] = $roadTransport;
    }

    public function remove(RoadTransport $roadTransport): void
    {
        if (!isset($this->roadTransports[(string) $roadTransport->id()])) {
            return;
        }

        unset($this->roadTransports[(string) $roadTransport->id()]);
    }

    public function find(RoadTransportId $roadTransportId): ?RoadTransport
    {
        return (isset($this->roadTransports[(string) $roadTransportId]) && false == $this->roadTransports[(string) $roadTransportId]->isRemoved())
            ? $this->roadTransports[(string) $roadTransportId]
            : null
        ;
    }

    public function findOneByName(string $name): ?RoadTransport
    {
        foreach ($this->roadTransports as $roadTransport) {
            if (true == $roadTransport->isRemoved()) {
                continue;
            }

            if (strtolower(trim((string) $roadTransport->name())) === strtolower(trim($name))) {
                return $roadTransport;
            }
        }

        return null;
    }

    /** @return array<int, RoadTransport> */
    public function findByFilter(RoadTransportFilter $filter): array
    {
        $results = [];

        if (0 === count($this->roadTransports)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->roadTransports as $roadTransport) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $roadTransport->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $roadTransport->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $roadTransport;
        }

        return $results;
    }

    public function totalResultsByFilter(RoadTransportFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
