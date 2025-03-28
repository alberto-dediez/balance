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
        return new InMemoryMeasurementRepository(
            propertyRepository: PropertyRepositoryMother::inMemory()
        );
    }
}