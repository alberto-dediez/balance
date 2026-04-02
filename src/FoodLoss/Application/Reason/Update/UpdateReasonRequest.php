<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Update;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Application\CommandBus\Command;

class UpdateReasonRequest implements Command
{
    public function __construct(
        public ReasonId $reasonId,
        public string $description,
        public Priority $priority,
    ) {}
}
