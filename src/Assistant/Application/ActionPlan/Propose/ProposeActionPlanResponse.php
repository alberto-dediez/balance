<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Propose;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Shared\Application\CommandBus\CommandResponse;

class ProposeActionPlanResponse implements CommandResponse
{
    public function __construct(public ActionPlanId $planId) {}
}
