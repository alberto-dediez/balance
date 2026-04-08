<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineDoggyBagSignRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDoggyBagSignRepositoryTest extends DoggyBagSignRepositoryTest
{
    protected function createSUT(): DoggyBagSignRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDoggyBagSignRepository($manager);
    }
}
