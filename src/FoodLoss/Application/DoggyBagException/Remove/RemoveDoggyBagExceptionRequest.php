<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Remove;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Shared\Application\CommandBus\Command;

class RemoveDoggyBagExceptionRequest implements Command
{
    public function __construct(public DoggyBagExceptionId $doggyBagExceptionId) {}
}
