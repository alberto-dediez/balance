<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\Update;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Application\CommandBus\Command;

class UpdateActionRequest implements Command
{
    public function __construct(
        public ActionId $actionId,
        public string $description,
        public Priority $priority,
    ) {}
}
