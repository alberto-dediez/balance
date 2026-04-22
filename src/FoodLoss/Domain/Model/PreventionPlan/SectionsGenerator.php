<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

interface SectionsGenerator
{
    public const int DEFAULT_SECTIONS_COUNT = 5;

    public function generate(PreventionPlanVersion $version): void;
}
