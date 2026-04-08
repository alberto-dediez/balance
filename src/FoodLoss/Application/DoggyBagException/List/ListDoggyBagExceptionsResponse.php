<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\List;

class ListDoggyBagExceptionsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException> $doggyBagExceptions */
    public function __construct(public array $doggyBagExceptions = [], public int $totalResults = 0) {}
}
