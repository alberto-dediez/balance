<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;

class TripCalculationRepositoryMother
{
    public static function inMemory(): InMemoryTripCalculationRepository
    {
        return new InMemoryTripCalculationRepository();
    }
}
