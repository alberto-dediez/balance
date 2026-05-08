<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanDraftTranslator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Shared\Domain\Model\Locale\Locale;

final class NullPreventionPlanDraftTranslator implements PreventionPlanDraftTranslator
{
    public function translate(PreventionPlanVersion $version, Locale $targetLocale): void {}
}
