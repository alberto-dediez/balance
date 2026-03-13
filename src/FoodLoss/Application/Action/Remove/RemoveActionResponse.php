<?php

namespace App\FoodLoss\Application\Action\Remove;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveActionResponse implements CommandResponse
{
    public function __construct(public ActionId $actionId)
    {
        
    }
}