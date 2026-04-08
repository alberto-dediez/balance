<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;

class InMemoryDoggyBagSignRepositoryTest extends DoggyBagSignRepositoryTest
{
    protected function createSUT(): DoggyBagSignRepository
    {
        return DoggyBagSignRepositoryMother::inMemory();
    }
}
