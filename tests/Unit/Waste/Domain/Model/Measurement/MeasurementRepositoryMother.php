<?php

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Infrastructure\Repository\Measurement\InMemoryMeasurementRepository;

class MeasurementRepositoryMother
{
    public static function inMemory(): InMemoryMeasurementRepository
    {
        return self::fromContainerRepository(
            ContainerRepositoryMother::inMemory()
        );
    }

    public static function fromContainerRepository(ContainerRepository $containerRepository)
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();

        return new InMemoryMeasurementRepository(
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository
        );
    }
}