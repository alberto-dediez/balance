<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Measurement;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryMeasurementRepository;

class MeasurementRepositoryMother
{
    public static function inMemory(): InMemoryMeasurementRepository
    {
        return new InMemoryMeasurementRepository();
    }
}
