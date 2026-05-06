<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\GeneratePdf;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class GeneratePdfPreventionPlanRequest implements Command
{
    public function __construct(
        public readonly PreventionPlanId $preventionPlanId,
        public readonly int $versionNumber,
        public readonly Locale $locale,
    ) {}
}
