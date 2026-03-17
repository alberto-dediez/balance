<?php

namespace App\FoodLoss\Application\ServiceCategory\Register;

use App\FoodLoss\Domain\Model\ServiceCategory\Priority;
use App\Shared\Application\CommandBus\Command;

class RegisterServiceCategoryRequest implements Command
{
    public function __construct(
        public string $name = ''
    ) {
        
    }
}