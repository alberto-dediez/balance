<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Update;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateDoggyBagExceptionResponse implements CommandResponse
{
    public function __construct(
        public DoggyBagExceptionId $doggyBagExceptionId,
        public string $description,
    ) {}
}
