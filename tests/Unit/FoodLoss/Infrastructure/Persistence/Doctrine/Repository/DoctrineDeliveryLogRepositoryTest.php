<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineDeliveryLogRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDeliveryLogRepositoryTest extends DeliveryLogRepositoryTest
{
    protected function createSUT(): DeliveryLogRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDeliveryLogRepository($manager);
    }
}
