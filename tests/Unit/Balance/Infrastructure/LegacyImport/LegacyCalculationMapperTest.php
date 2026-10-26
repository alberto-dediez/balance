<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyCalculationMapper;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use PHPUnit\Framework\TestCase;

class LegacyCalculationMapperTest extends TestCase
{
    public function testACalculationWithOriginGoesToMobilityInKilograms(): void
    {
        $linkId = TripCalculationLinkId::generate();
        $planeId = MobilityId::generate();
        $calculation = LegacyCalculationMother::from('sao', transport: 'plane', emissionsTotalGrams: 166809.7);

        $request = (new LegacyCalculationMapper())->request($linkId, $calculation, ['plane' => $planeId]);

        $this->assertTrue($linkId->equals($request->tripCalculationLinkId));
        $this->assertSame('166.8097', $request->mobilityEmissions->co2eq());
        $this->assertSame('0', $request->overnightEmissions->co2eq());
        $this->assertSame(403.2785, $request->distanceKm);
        $this->assertSame($planeId, $request->mobilityId);
        $this->assertNull($request->vehicleOccupants);
        $this->assertSame(3, $request->people);
        $this->assertSame(2, $request->nights);
        $this->assertFalse($request->roundTrip);
        $this->assertNotNull($request->origin);
        $this->assertSame('place-madrid', $request->origin->reference);
        $this->assertSame('Madrid, Spain', $request->origin->label);
        $this->assertSame(40.48, $request->origin->point?->latitude);
        $this->assertSame('place-sevilla', $request->destination?->reference);
        $this->assertEquals(new \DateTimeImmutable('2024-08-29 14:33:50'), $request->registeredAt);
    }

    public function testACarCalculationKeepsThePersonsAsVehicleOccupants(): void
    {
        $calculation = LegacyCalculationMother::from('sao', transport: 'car');

        $request = (new LegacyCalculationMapper())->request(TripCalculationLinkId::generate(), $calculation, []);

        $this->assertSame(3, $request->vehicleOccupants);
        $this->assertNull($request->mobilityId);
    }

    public function testACalculationWithoutOriginGoesToOvernightWithoutDistance(): void
    {
        $calculation = LegacyCalculationMother::from('sao', transport: 'plane', withOrigin: false, emissionsTotalGrams: 25000.0);

        $request = (new LegacyCalculationMapper())->request(TripCalculationLinkId::generate(), $calculation, []);

        $this->assertSame('0', $request->mobilityEmissions->co2eq());
        $this->assertSame('25', $request->overnightEmissions->co2eq());
        $this->assertNull($request->distanceKm);
        $this->assertNull($request->origin);
    }

    public function testACalculationWithTheSameDateAndEmissionsWithinTheToleranceIsDuplicated(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();
        $calculation = LegacyCalculationMother::from('sao', emissionsTotalGrams: 166809.7, createdAt: '2024-08-29 14:33:50');

        $existing = [TripCalculationMother::registeredAt($linkId, '2024-08-29 14:33:50', new Emissions(co2eq: '166.8101'))];

        $this->assertTrue((new LegacyCalculationMapper())->isDuplicated($calculation, $existing));
    }

    public function testACalculationWithEmissionsOutsideTheToleranceIsNotDuplicated(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();
        $calculation = LegacyCalculationMother::from('sao', emissionsTotalGrams: 166809.7, createdAt: '2024-08-29 14:33:50');

        $existing = [TripCalculationMother::registeredAt($linkId, '2024-08-29 14:33:50', new Emissions(co2eq: '166.8120'))];

        $this->assertFalse((new LegacyCalculationMapper())->isDuplicated($calculation, $existing));
    }

    public function testACalculationOfAnotherDateIsNotDuplicated(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();
        $calculation = LegacyCalculationMother::from('sao', emissionsTotalGrams: 166809.7, createdAt: '2024-08-29 14:33:50');

        $existing = [TripCalculationMother::registeredAt($linkId, '2024-08-29 14:33:51', new Emissions(co2eq: '166.8097'))];

        $this->assertFalse((new LegacyCalculationMapper())->isDuplicated($calculation, $existing));
    }
}
