<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Container;

use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryTest;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class InMemoryContainerRepositoryTest extends ContainerRepositoryTest
{
    public function createSUT(): ContainerRepository
    {
        return new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );
    }
}