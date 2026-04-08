<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Register;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterDoggyBagSignResponse implements CommandResponse
{
    public function __construct(
        public DoggyBagSignId $doggyBagSignId,
        public string $name,
        public string $path,
    ) {}
}
