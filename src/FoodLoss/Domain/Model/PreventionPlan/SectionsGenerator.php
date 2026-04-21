<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

interface SectionsGenerator
{
    public function generate(PreventionPlanVersion $version): void;
}
