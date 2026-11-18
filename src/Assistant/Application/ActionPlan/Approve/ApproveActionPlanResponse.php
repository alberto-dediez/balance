<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Approve;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Shared\Application\CommandBus\CommandResponse;

class ApproveActionPlanResponse implements CommandResponse
{
    public function __construct(public ActionPlanId $planId, public MessageId $messageId) {}
}
