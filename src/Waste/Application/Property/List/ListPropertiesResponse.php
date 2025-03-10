<?php

namespace App\Waste\Application\Property\List;

class ListPropertiesResponse
{
    public function __construct(public array $properties = [], public int $totalResults = 0)
    {
        
    }
}