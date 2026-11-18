<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Discard;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Shared\Application\CommandBus\Command;

class DiscardActionPlanRequest implements Command
{
    public function __construct(
        public ActionPlanId $planId,
        public DiscardReason $reason = DiscardReason::User,
    ) {}
}
