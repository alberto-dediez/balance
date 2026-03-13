<?php

namespace App\FoodLoss\Application\Action\Remove;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Application\CommandBus\Command;

class RemoveActionRequest implements Command
{
    public function __construct(public ActionId $actionId)
    {

    }
}