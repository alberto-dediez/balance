<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

interface ServiceCategoryRepository
{
    public function add(ServiceCategory $serviceCategory): void;

    public function remove(ServiceCategory $serviceCategory): void;

    public function find(ServiceCategoryId $serviceCategoryId): ?ServiceCategory;

    /** @return array<ServiceCategory> */
    public function findByFilter(ServiceCategoryFilter $filter): array;

    public function totalResultsByFilter(ServiceCategoryFilter $filter): int;
}
