<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCriticalPointRepository implements CriticalPointRepository
{
    /** @param array<string, CriticalPoint> $criticalPoints */
    public function __construct(
        private array $criticalPoints = [],
    ) {}

    public function add(CriticalPoint $criticalPoint): void
    {
        $this->criticalPoints[(string) $criticalPoint->id()] = $criticalPoint;
    }

    public function remove(CriticalPoint $criticalPoint): void
    {
        if (!isset($this->criticalPoints[(string) $criticalPoint->id()])) {
            return;
        }

        unset($this->criticalPoints[(string) $criticalPoint->id()]);
    }

    public function find(CriticalPointId $criticalPointId): ?CriticalPoint
    {
        return (isset($this->criticalPoints[(string) $criticalPointId]) && false == $this->criticalPoints[(string) $criticalPointId]->isRemoved())
            ? $this->criticalPoints[(string) $criticalPointId]
            : null
        ;
    }

    /** @return array<int, CriticalPoint> */
    public function findByFilter(CriticalPointFilter $filter): array
    {
        $results = [];

        if (0 === count($this->criticalPoints)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->criticalPoints as $criticalPoint) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $criticalPoint->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (null == $criticalPoint->accountId()
                    || false === $filter->accountId->equals($criticalPoint->accountId()))
            ) {
                continue;
            }

            if (
                !empty($filter->propertyId)
                && (string) $criticalPoint->propertyId() !== $filter->propertyId
            ) {
                continue;
            }

            if ($filter->serviceCategoryId instanceof ServiceCategoryId) {
                $linked = false;
                foreach ($criticalPoint->serviceCategoryIds() as $id) {
                    if ($id->equals($filter->serviceCategoryId)) {
                        $linked = true;
                        break;
                    }
                }
                if (!$linked) {
                    continue;
                }
            }

            if ($filter->actionId instanceof ActionId && !$criticalPoint->actions()->has($filter->actionId)) {
                continue;
            }

            if (true == $criticalPoint->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $criticalPoint;
        }

        return $results;
    }

    public function totalResultsByFilter(CriticalPointFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
