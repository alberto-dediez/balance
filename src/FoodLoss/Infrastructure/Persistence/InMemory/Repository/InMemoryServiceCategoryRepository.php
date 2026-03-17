<?php

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryServiceCategoryRepository implements ServiceCategoryRepository
{
    public function __construct(private LocaleGetter $localeGetter, private array $serviceCategories = [])
    {

    }

    public function add(ServiceCategory $serviceCategory): void
    {
        if (isset($this->ServiceCategories[(string) $serviceCategory->id()])) {
            return ;
        }

        $serviceCategory->translations()->setLocale($this->localeGetter->locale());

        $this->serviceCategories[(string) $serviceCategory->id()] = $serviceCategory;
    }

    public function remove(ServiceCategory $serviceCategory): void
    {
        if (!isset($this->serviceCategories[(string) $serviceCategory->id()])) {
            return ;
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

    public function findByFilter(ServiceCategoryFilter $filter): array
    {
        $results = [];

        if (count($this->serviceCategories) == 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->serviceCategories as $serviceCategory) {
            if (
                !empty($filter->name)
                && false === strpos(strtolower($serviceCategory->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $serviceCategory->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

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
