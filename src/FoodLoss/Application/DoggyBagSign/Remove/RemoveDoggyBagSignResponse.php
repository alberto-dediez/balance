<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Remove;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveDoggyBagSignResponse implements CommandResponse
{
    public function __construct(public DoggyBagSignId $doggyBagSignId) {}
}
