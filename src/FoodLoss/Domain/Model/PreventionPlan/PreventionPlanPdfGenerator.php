<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Shared\Domain\Model\Locale\Locale;

interface PreventionPlanPdfGenerator
{
    /**
     * @return string $path
     */
    public function generate(PreventionPlan $preventionPlan, PreventionPlanVersion $version, Locale $locale): string;
}
