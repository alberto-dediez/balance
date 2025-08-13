<?php

namespace App\Waste\Application\DailyWaste\List;

class ListDailyWastesResponse
{
    public function __construct(public array $dailyWastes = [], public int $totalResults = 0)
    {
        
    }
}