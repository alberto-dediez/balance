<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Container;

use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;

class InMemoryContainerRepositoryTest extends ContainerRepositoryTest
{
    public function createSUT(): ContainerRepository
    {
        return ContainerRepositoryMother::inMemory();
    }
}