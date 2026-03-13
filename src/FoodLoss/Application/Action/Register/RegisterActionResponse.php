<?php

namespace App\FoodLoss\Application\Action\Register;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterActionResponse implements CommandResponse
{
    public function __construct(
        public ActionId $actionId, 
        public string $description,
        public Priority $priority
    ) {
        
    }
}