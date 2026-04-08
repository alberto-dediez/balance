<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Infrastructure\Persistence\InMemory\Repository\InMemoryDoggyBagExceptionRepository;

class DoggyBagExceptionRepositoryMother
{
    public static function inMemory(): InMemoryDoggyBagExceptionRepository
    {
        return new InMemoryDoggyBagExceptionRepository();
    }
}
