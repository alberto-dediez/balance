<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Discard;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Shared\Application\CommandBus\CommandResponse;

class DiscardActionPlanResponse implements CommandResponse
{
    public function __construct(public ActionPlanId $planId) {}
}
