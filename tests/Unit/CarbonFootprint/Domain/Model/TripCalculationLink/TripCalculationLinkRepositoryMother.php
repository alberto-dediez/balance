<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;

class TripCalculationLinkRepositoryMother
{
    public static function inMemory(): InMemoryTripCalculationLinkRepository
    {
        return new InMemoryTripCalculationLinkRepository();
    }
}
