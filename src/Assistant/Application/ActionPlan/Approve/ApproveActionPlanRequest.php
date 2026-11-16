<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Approve;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Shared\Application\CommandBus\Command;

class ApproveActionPlanRequest implements Command
{
    public function __construct(public ActionPlanId $planId) {}
}
