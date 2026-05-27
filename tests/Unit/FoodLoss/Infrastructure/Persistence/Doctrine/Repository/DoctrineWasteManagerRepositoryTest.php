<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineWasteManagerRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    protected function createSUT(): WasteManagerRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineWasteManagerRepository($manager);
    }
}
