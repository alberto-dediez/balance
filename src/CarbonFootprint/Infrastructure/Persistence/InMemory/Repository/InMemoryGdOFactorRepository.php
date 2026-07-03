<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryGdOFactorRepository implements GdOFactorRepository
{
    /** @param array<string, GdOFactor> $gdOFactors */
    public function __construct(
        private array $gdOFactors = [],
    ) {}

    public function add(GdOFactor $gdOFactor): void
    {
        if (isset($this->gdOFactors[(string) $gdOFactor->id()])) {
            return;
        }

        $this->gdOFactors[(string) $gdOFactor->id()] = $gdOFactor;
    }

    public function remove(GdOFactor $gdOFactor): void
    {
        if (!isset($this->gdOFactors[(string) $gdOFactor->id()])) {
            return;
        }

        unset($this->gdOFactors[(string) $gdOFactor->id()]);
    }

    public function find(GdOFactorId $gdOFactorId): ?GdOFactor
    {
        return (isset($this->gdOFactors[(string) $gdOFactorId]) && false == $this->gdOFactors[(string) $gdOFactorId]->isRemoved())
            ? $this->gdOFactors[(string) $gdOFactorId]
            : null
        ;
    }

    /** @return array<int, GdOFactor> */
    public function findByFilter(GdOFactorFilter $filter): array
    {
        $results = [];

        if (0 === count($this->gdOFactors)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->gdOFactors as $gdOFactor) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $gdOFactor->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $gdOFactor->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $gdOFactor;
        }

        return $results;
    }

    public function totalResultsByFilter(GdOFactorFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
