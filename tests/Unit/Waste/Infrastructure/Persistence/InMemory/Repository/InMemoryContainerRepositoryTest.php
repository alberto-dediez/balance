<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryTest;
use App\Waste\Domain\Model\Container\ContainerRepository;

class InMemoryContainerRepositoryTest extends ContainerRepositoryTest
{
    public function createSUT(): ContainerRepository
    {
        return ContainerRepositoryMother::inMemory();
    }
}
