<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Measurement;

use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class InMemoryMeasurementRepositoryTest extends MeasurementRepositoryTest
{
    public function createSUT(): MeasurementRepository
    {
        return MeasurementRepositoryMother::inMemory();
    }
}