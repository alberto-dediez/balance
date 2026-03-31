<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Action\Remove;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Application\CommandBus\Command;

class RemoveActionRequest implements Command
{
    public function __construct(public ActionId $actionId) {}
}
