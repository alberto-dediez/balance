<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySurplusRepository implements SurplusRepository
{
    /** @param array<string, Surplus> $surpluses */
    public function __construct(
        private array $surpluses = [],
    ) {}

    public function add(Surplus $surplus): void
    {
        $this->surpluses[(string) $surplus->id()] = $surplus;
    }

    public function remove(Surplus $surplus): void
    {
        if (!isset($this->surpluses[(string) $surplus->id()])) {
            return;
        }

        unset($this->surpluses[(string) $surplus->id()]);
    }

    public function find(SurplusId $surplusId): ?Surplus
    {
        return (isset($this->surpluses[(string) $surplusId]) && false == $this->surpluses[(string) $surplusId]->isRemoved())
            ? $this->surpluses[(string) $surplusId]
            : null
        ;
    }

    /** @return array<int, Surplus> */
    public function findByFilter(SurplusFilter $filter): array
    {
        $results = [];

        if (0 === count($this->surpluses)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->surpluses as $surplus) {
            if (true == $surplus->isRemoved()) {
                continue;
            }

            if ($filter->accountId instanceof AccountId && !$surplus->accountId()->equals($filter->accountId)) {
                continue;
            }

            if ($filter->dateFrom instanceof \DateTimeImmutable && $surplus->date() < $filter->dateFrom) {
                continue;
            }

            if ($filter->dateTo instanceof \DateTimeImmutable && $surplus->date() > $filter->dateTo) {
                continue;
            }

            if (null !== $filter->foodType && $surplus->foodType() !== $filter->foodType) {
                continue;
            }

            if ($filter->destination instanceof SurplusDestination && $surplus->destination() !== $filter->destination) {
                continue;
            }

            if ($filter->serviceCategoryId instanceof ServiceCategoryId && !$surplus->serviceCategoryId()->equals($filter->serviceCategoryId)) {
                continue;
            }

            if ($filter->criticalPointId instanceof CriticalPointId && !$surplus->criticalPointId()->equals($filter->criticalPointId)) {
                continue;
            }

            if ($filter->surplusCauseId instanceof SurplusCauseId && !$surplus->surplusCauseId()->equals($filter->surplusCauseId)) {
                continue;
            }

            if ($filter->incidenceId instanceof IncidenceId && (null === $surplus->incidenceId() || !$surplus->incidenceId()->equals($filter->incidenceId))) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $surplus;
        }

        return $results;
    }

    public function totalResultsByFilter(SurplusFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
