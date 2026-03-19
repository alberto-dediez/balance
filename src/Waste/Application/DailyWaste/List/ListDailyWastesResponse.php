<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\List;

class ListDailyWastesResponse
{
    public function __construct(public array $dailyWastes = [], public int $totalResults = 0) {}
}
