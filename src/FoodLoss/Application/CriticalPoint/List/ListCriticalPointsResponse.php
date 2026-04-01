<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\List;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;

class ListCriticalPointsResponse
{
    /** @param array<CriticalPoint> $criticalPoints */
    public function __construct(public array $criticalPoints = [], public int $totalResults = 0) {}
}
