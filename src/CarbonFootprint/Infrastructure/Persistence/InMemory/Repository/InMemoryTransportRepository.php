<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTransportRepository implements TransportRepository
{
    /** @param array<string, Transport> $transports */
    public function __construct(
        private array $transports = [],
    ) {}

    public function add(Transport $transport): void
    {
        if (isset($this->transports[(string) $transport->id()])) {
            return;
        }

        $this->transports[(string) $transport->id()] = $transport;
    }

    public function remove(Transport $transport): void
    {
        if (!isset($this->transports[(string) $transport->id()])) {
            return;
        }

        unset($this->transports[(string) $transport->id()]);
    }

    public function find(TransportId $transportId): ?Transport
    {
        return (isset($this->transports[(string) $transportId]) && false == $this->transports[(string) $transportId]->isRemoved())
            ? $this->transports[(string) $transportId]
            : null
        ;
    }

    /** @return array<int, Transport> */
    public function findByFilter(TransportFilter $filter): array
    {
        $results = [];

        if (0 === count($this->transports)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->transports as $transport) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $transport->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->unit && $transport->unit() !== $filter->unit) {
                continue;
            }

            if (null !== $filter->mode && $transport->mode() !== $filter->mode) {
                continue;
            }

            if (true == $transport->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $transport;
        }

        return $results;
    }

    public function totalResultsByFilter(TransportFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
