<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\List;

class ListDoggyBagSignsResponse
{
    /** @param array<\App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign> $doggyBagSigns */
    public function __construct(public array $doggyBagSigns = [], public int $totalResults = 0) {}
}
