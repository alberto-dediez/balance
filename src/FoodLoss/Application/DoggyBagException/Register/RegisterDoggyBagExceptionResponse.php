<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Register;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterDoggyBagExceptionResponse implements CommandResponse
{
    public function __construct(
        public DoggyBagExceptionId $doggyBagExceptionId,
        public string $description,
    ) {}
}
