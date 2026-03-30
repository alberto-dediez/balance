<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryTest;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

class InMemoryMeasurementRepositoryTest extends MeasurementRepositoryTest
{
    public function createSUT(): MeasurementRepository
    {
        return MeasurementRepositoryMother::inMemory();
    }
}
