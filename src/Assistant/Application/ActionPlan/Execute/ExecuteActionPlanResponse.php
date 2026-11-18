<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Execute;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Shared\Application\CommandBus\CommandResponse;

class ExecuteActionPlanResponse implements CommandResponse
{
    public function __construct(public ActionPlanId $planId, public ActionPlanStatus $status) {}
}
