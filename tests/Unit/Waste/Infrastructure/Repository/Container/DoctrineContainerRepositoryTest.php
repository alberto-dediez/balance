<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Container;

use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryTest;
use App\Waste\Infrastructure\Repository\Container\DoctrineContainerRepository;

class DoctrineContainerRepositoryTest extends ContainerRepositoryTest
{
    public function createSUT(): ContainerRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineContainerRepository($manager);
    }
}