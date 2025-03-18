<?php

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;

class ContainerRepositoryMother
{
    public static function inMemory(): InMemoryContainerRepository
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();

        return new InMemoryContainerRepository(
            propertyRepository: $propertyRepository
        );
    }
}