<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository\DoctrineProtocolRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineProtocolRepositoryTest extends ProtocolRepositoryTest
{
    protected function createSUT(): ProtocolRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineProtocolRepository($manager);
    }
}
