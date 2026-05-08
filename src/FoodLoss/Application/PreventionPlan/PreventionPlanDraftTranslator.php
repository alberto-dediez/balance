<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Shared\Domain\Model\Locale\Locale;

interface PreventionPlanDraftTranslator
{
    public function translate(PreventionPlanVersion $version, Locale $targetLocale): void;
}
