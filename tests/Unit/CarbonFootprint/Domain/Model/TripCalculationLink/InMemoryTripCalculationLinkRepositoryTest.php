<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;

class InMemoryTripCalculationLinkRepositoryTest extends TripCalculationLinkRepositoryTest
{
    protected function createSUT(): TripCalculationLinkRepository
    {
        return TripCalculationLinkRepositoryMother::inMemory();
    }
}
