<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDoggyBagSignRepository;

class DoggyBagSignRepositoryMother
{
    public static function inMemory(): InMemoryDoggyBagSignRepository
    {
        return new InMemoryDoggyBagSignRepository();
    }
}
