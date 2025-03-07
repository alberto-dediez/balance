<?php

namespace App\Waste\Application\Waste\List;

class ListWastesResponse
{
    public function __construct(public array $accounts = [], public int $totalResults = 0)
    {
        
    }
}