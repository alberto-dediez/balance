<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrinePackagingRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePackagingRepositoryTest extends PackagingRepositoryTest
{
    protected function createSUT(): PackagingRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePackagingRepository($manager);
    }
}
