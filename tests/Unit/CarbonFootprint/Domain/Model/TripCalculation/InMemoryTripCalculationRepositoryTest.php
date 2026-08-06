<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;

class InMemoryTripCalculationRepositoryTest extends TripCalculationRepositoryTest
{
    protected function createSUT(): TripCalculationRepository
    {
        return TripCalculationRepositoryMother::inMemory();
    }
}
