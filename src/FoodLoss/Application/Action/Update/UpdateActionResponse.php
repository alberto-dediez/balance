<?php

namespace App\FoodLoss\Application\Action\Update;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateActionResponse implements CommandResponse
{
    public function __construct(
        public ActionId $actionId, 
        public string $description,
        public string $priority
    ) {
        
    }
}