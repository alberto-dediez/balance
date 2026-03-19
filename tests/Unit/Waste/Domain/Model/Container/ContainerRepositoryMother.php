<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;

class ContainerRepositoryMother
{
    public static function inMemory(): InMemoryContainerRepository
    {
        return new InMemoryContainerRepository();
    }
}
