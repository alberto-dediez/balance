<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Shared\Application\CommandBus\Command;

class UpdateDoggyBagExceptionRequest implements Command
{
    public function __construct(
        public DoggyBagExceptionId $doggyBagExceptionId,
        public string $description,
        public ?CriticalPointId $criticalPointId = null,
    ) {}
}
