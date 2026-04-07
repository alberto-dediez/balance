<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\Register;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPreventionPlanResponse implements CommandResponse
{
    public function __construct(
        public PreventionPlanId $preventionPlanId,
        public string $name,
    ) {}
}
