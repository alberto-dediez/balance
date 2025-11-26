<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryTest;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Infrastructure\Persistence\Doctrine\Repository\DoctrineContainerRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineContainerRepositoryTest extends ContainerRepositoryTest
{
    public function createSUT(): ContainerRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineContainerRepository($manager);
    }
}
