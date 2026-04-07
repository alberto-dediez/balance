<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPreventionPlanRepository implements PreventionPlanRepository
{
    /** @param array<string, PreventionPlan> $preventionPlans */
    public function __construct(
        private array $preventionPlans = [],
    ) {}

    public function add(PreventionPlan $preventionPlan): void
    {
        $this->preventionPlans[(string) $preventionPlan->id()] = $preventionPlan;
    }

    public function remove(PreventionPlan $preventionPlan): void
    {
        if (!isset($this->preventionPlans[(string) $preventionPlan->id()])) {
            return;
        }

        unset($this->preventionPlans[(string) $preventionPlan->id()]);
    }

    public function find(PreventionPlanId $preventionPlanId): ?PreventionPlan
    {
        return (isset($this->preventionPlans[(string) $preventionPlanId]) && false == $this->preventionPlans[(string) $preventionPlanId]->isRemoved())
            ? $this->preventionPlans[(string) $preventionPlanId]
            : null
        ;
    }

    /** @return array<int, PreventionPlan> */
    public function findByFilter(PreventionPlanFilter $filter): array
    {
        $results = [];

        if (0 === count($this->preventionPlans)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->preventionPlans as $preventionPlan) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $preventionPlan->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId) {
                $planAccountId = $preventionPlan->accountId();
                if (!$planAccountId instanceof AccountId || !$filter->accountId->equals($planAccountId)) {
                    continue;
                }
            }

            if ($filter->propertyId instanceof PropertyId) {
                $propertyIds = $preventionPlan->propertyIds();
                if (count($propertyIds) > 0) {
                    $found = false;
                    foreach ($propertyIds as $propertyId) {
                        if ($filter->propertyId->equals($propertyId)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        continue;
                    }
                }
            }

            if (true == $preventionPlan->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $preventionPlan;
        }

        return $results;
    }

    public function totalResultsByFilter(PreventionPlanFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
