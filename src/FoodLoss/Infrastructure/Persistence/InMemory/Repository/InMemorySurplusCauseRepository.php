<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseFilter;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySurplusCauseRepository implements SurplusCauseRepository
{
    /** @param array<string, SurplusCause> $surplusCauses */
    public function __construct(
        private array $surplusCauses = [],
    ) {}

    public function add(SurplusCause $surplusCause): void
    {
        if (isset($this->surplusCauses[(string) $surplusCause->id()])) {
            return;
        }

        $this->surplusCauses[(string) $surplusCause->id()] = $surplusCause;
    }

    public function remove(SurplusCause $surplusCause): void
    {
        if (!isset($this->surplusCauses[(string) $surplusCause->id()])) {
            return;
        }

        unset($this->surplusCauses[(string) $surplusCause->id()]);
    }

    public function find(SurplusCauseId $surplusCauseId): ?SurplusCause
    {
        return (isset($this->surplusCauses[(string) $surplusCauseId]) && false == $this->surplusCauses[(string) $surplusCauseId]->isRemoved())
            ? $this->surplusCauses[(string) $surplusCauseId]
            : null
        ;
    }

    /** @return array<int, SurplusCause> */
    public function findByFilter(SurplusCauseFilter $filter): array
    {
        $results = [];

        if (0 === count($this->surplusCauses)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->surplusCauses as $surplusCause) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $surplusCause->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $surplusCause->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $surplusCause;
        }

        return $results;
    }

    public function totalResultsByFilter(SurplusCauseFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
