<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste\List;

use App\Waste\Domain\Model\DailyWaste\DailyWaste;

class ListDailyWastesResponse
{
    /** @param array<int, DailyWaste> $dailyWastes */
    public function __construct(public array $dailyWastes = [], public int $totalResults = 0) {}
}
