<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineDoggyBagExceptionRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDoggyBagExceptionRepositoryTest extends DoggyBagExceptionRepositoryTest
{
    protected function createSUT(): DoggyBagExceptionRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDoggyBagExceptionRepository($manager);
    }
}
