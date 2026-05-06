<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\List;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;

class ListSurplusCausesResponse
{
    /** @param array<SurplusCause> $surplusCauses */
    public function __construct(public array $surplusCauses = [], public int $totalResults = 0) {}
}
