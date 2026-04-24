<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

class PlanWithoutCompanyException extends \RuntimeException
{
    public function __construct(PreventionPlanId $planId)
    {
        parent::__construct(
            sprintf('%s no tiene una company asignada', (string) $planId),
            504,
        );
    }
}
