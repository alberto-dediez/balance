<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryOvernightStayRepository implements OvernightStayRepository
{
    /** @param array<string, OvernightStay> $overnightStays */
    public function __construct(
        private array $overnightStays = [],
    ) {}

    public function add(OvernightStay $overnightStay): void
    {
        if (isset($this->overnightStays[(string) $overnightStay->id()])) {
            return;
        }

        $this->overnightStays[(string) $overnightStay->id()] = $overnightStay;
    }

    public function remove(OvernightStay $overnightStay): void
    {
        if (!isset($this->overnightStays[(string) $overnightStay->id()])) {
            return;
        }

        unset($this->overnightStays[(string) $overnightStay->id()]);
    }

    public function find(OvernightStayId $overnightStayId): ?OvernightStay
    {
        return (isset($this->overnightStays[(string) $overnightStayId]) && false == $this->overnightStays[(string) $overnightStayId]->isRemoved())
            ? $this->overnightStays[(string) $overnightStayId]
            : null
        ;
    }

    /** @return array<int, OvernightStay> */
    public function findByFilter(OvernightStayFilter $filter): array
    {
        $results = [];

        if (0 === count($this->overnightStays)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->overnightStays as $overnightStay) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $overnightStay->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $overnightStay->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $overnightStay;
        }

        return $results;
    }

    public function totalResultsByFilter(OvernightStayFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
