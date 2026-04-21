<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateSections;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdatePreventionPlanSectionsResponse implements CommandResponse
{
    public function __construct(
        public PreventionPlanId $preventionPlanId,
    ) {}
}
