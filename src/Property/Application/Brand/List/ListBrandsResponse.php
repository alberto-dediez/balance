<?php

namespace App\Property\Application\Brand\List;

class ListBrandsResponse
{
    public function __construct(public array $brands = [], public int $totalResults = 0)
    {
        
    }
}