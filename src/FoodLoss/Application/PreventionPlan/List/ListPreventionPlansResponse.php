<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\List;

class ListPreventionPlansResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan> $preventionPlans */
    public function __construct(
        public array $preventionPlans = [],
        public int $totalResults = 0,
    ) {}
}
