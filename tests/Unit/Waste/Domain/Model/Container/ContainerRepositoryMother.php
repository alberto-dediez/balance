<?php

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class ContainerRepositoryMother
{
    public static function inMemory(): InMemoryContainerRepository
    {
        return new InMemoryContainerRepository();
    }
}