<?php

namespace App\FoodLoss\Application\ServiceCategory\List;

class ListServiceCategoriesResponse
{
    public function __construct(public array $serviceCategories = [], public int $totalResults = 0)
    {
        
    }
}