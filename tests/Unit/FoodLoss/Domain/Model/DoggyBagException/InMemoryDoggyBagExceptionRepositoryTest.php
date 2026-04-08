<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;

class InMemoryDoggyBagExceptionRepositoryTest extends DoggyBagExceptionRepositoryTest
{
    protected function createSUT(): DoggyBagExceptionRepository
    {
        return DoggyBagExceptionRepositoryMother::inMemory();
    }
}
