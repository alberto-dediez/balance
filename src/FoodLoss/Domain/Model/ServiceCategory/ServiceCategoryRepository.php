<?php

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;

interface ServiceCategoryRepository
{
    public function add(ServiceCategory $serviceCategory): void;

    public function remove(ServiceCategory $serviceCategory): void;

    public function find(ServiceCategoryId $serviceCategoryId): ?ServiceCategory;

    public function findByFilter(ServiceCategoryFilter $filter): array;

    public function totalResultsByFilter(ServiceCategoryFilter $filter): int;
}