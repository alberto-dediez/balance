<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\List;

class ListServiceCategoriesResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory> $serviceCategories */
    public function __construct(public array $serviceCategories = [], public int $totalResults = 0) {}
}
