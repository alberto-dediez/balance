<?php

namespace App\Property\Application\Category\List;

class ListCategoriesResponse
{
    public function __construct(public array $categories = [], public int $totalResults = 0)
    {
        
    }
}