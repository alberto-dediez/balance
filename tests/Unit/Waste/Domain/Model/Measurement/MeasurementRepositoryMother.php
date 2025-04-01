<?php

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Waste\Infrastructure\Repository\Measurement\InMemoryMeasurementRepository;

class MeasurementRepositoryMother
{
    public static function inMemory(): InMemoryMeasurementRepository
    {
        return new InMemoryMeasurementRepository();
    }
}