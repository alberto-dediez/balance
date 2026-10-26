<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use PHPUnit\Framework\TestCase;

class TripCalculationTest extends TestCase
{
    public function testRegistersWithTheGivenRegistrationDate(): void
    {
        $registeredAt = new \DateTimeImmutable('2024-08-29 10:15:30');

        $tripCalculation = $this->register($registeredAt);

        $this->assertEquals($registeredAt, $tripCalculation->registeredAt());
    }

    public function testRegistersNowWithoutRegistrationDate(): void
    {
        $before = new \DateTimeImmutable();

        $tripCalculation = $this->register(null);

        $this->assertGreaterThanOrEqual($before, $tripCalculation->registeredAt());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $tripCalculation->registeredAt());
    }

    private function register(?\DateTimeImmutable $registeredAt): TripCalculation
    {
        $tripCalculation = new TripCalculation(TripCalculationId::generate());
        $tripCalculation->register(
            tripCalculationLinkId: TripCalculationLinkId::generate(),
            origin: null,
            destination: null,
            roundTrip: false,
            trips: 1,
            nights: 1,
            people: 1,
            legs: [],
            mobilityEmissions: new Emissions(),
            overnightEmissions: new Emissions(co2eq: '12.5'),
            emissionFactorGroupId: null,
            calculationYear: 2024,
            registeredAt: $registeredAt,
        );

        return $tripCalculation;
    }
}
