<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryServiceCategoryRepository implements ServiceCategoryRepository
{
    /** @param array<string, ServiceCategory> $serviceCategories */
    public function __construct(
        private array $serviceCategories = [],
    ) {}

    public function add(ServiceCategory $serviceCategory): void
    {
        if (isset($this->serviceCategories[(string) $serviceCategory->id()])) {
            return;
        }

        $this->serviceCategories[(string) $serviceCategory->id()] = $serviceCategory;
    }

    public function remove(ServiceCategory $serviceCategory): void
    {
        if (!isset($this->serviceCategories[(string) $serviceCategory->id()])) {
            return;
        }

        unset($this->serviceCategories[(string) $serviceCategory->id()]);
    }

    public function find(ServiceCategoryId $serviceCategoryId): ?ServiceCategory
    {
        return (isset($this->serviceCategories[(string) $serviceCategoryId]) && false == $this->serviceCategories[(string) $serviceCategoryId]->isRemoved())
            ? $this->serviceCategories[(string) $serviceCategoryId]
            : null
        ;
    }

    /** @return array<int, ServiceCategory> */
    public function findByFilter(ServiceCategoryFilter $filter): array
    {
        $results = [];

        if (0 === count($this->serviceCategories)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->serviceCategories as $serviceCategory) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $serviceCategory->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $serviceCategory->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $serviceCategory;
        }

        return $results;
    }

    public function totalResultsByFilter(ServiceCategoryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
