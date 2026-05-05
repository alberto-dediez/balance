<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ApproveDraft;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;

class ApproveDraftPreventionPlanRequest implements Command
{
    public function __construct(
        public readonly PreventionPlanId $preventionPlanId,
    ) {}
}
