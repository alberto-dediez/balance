<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\List;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;

class ListServiceCategoriesResponse
{
    /** @param array<ServiceCategory> $serviceCategories */
    public function __construct(public array $serviceCategories = [], public int $totalResults = 0) {}
}
