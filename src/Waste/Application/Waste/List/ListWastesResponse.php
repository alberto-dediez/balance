<?php

namespace App\Waste\Application\Waste\List;

class ListWastesResponse
{
    public function __construct(public array $wastes = [], public int $totalResults = 0)
    {
        
    }
}