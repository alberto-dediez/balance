<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Remove;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Application\CommandBus\Command;

class RemoveReasonRequest implements Command
{
    public function __construct(public ReasonId $reasonId) {}
}
