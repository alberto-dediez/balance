<?php

namespace App\Waste\Application\WasteManager\List;

class ListWasteManagersResponse
{
    public function __construct(public array $wasteManagers = [], public int $totalResults = 0)
    {
        
    }
}