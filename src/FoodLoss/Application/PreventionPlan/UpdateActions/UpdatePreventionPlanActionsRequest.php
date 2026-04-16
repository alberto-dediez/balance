<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateActions;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;

class UpdatePreventionPlanActionsRequest implements Command
{
    /** @param ProposedActionData[] $proposedActions */
    public function __construct(
        public PreventionPlanId $preventionPlanId,
        public array $proposedActions = [],
    ) {}
}
