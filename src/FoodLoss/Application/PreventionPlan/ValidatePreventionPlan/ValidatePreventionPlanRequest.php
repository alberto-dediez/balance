<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\ValidatePreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;

class ValidatePreventionPlanRequest
{
    public function __construct(public PreventionPlanId $preventionPlanId) {}
}
