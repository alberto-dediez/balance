<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class TripCalculationsAggregatorTest extends TestCase
{
    private TripCalculationsAggregator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new TripCalculationsAggregator(new TripLegsAccumulator());
    }

    public function testAggregatesNothingWithoutCalculations(): void
    {
        $aggregation = $this->sut->aggregate([]);

        $this->assertSame([], $aggregation->kmByMobilityId);
        $this->assertSame(0.0, $aggregation->overnightStays);
        $this->assertSame(0, $aggregation->calculations);
        $this->assertSame('0', $aggregation->totalEmissions->co2eq());
    }

    public function testAddsUpKilometresNightsAndEmissions(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();
        $mobilityId = (string) MobilityId::generate();

        $aggregation = $this->sut->aggregate([
            TripCalculationMother::singleLeg(
                tripCalculationLinkId: $linkId,
                mobilityId: $mobilityId,
                distanceKm: 100.0,
                nights: 2,
                people: 2,
                mobilityEmissions: new Emissions(co2eq: '10'),
            ),
            TripCalculationMother::singleLeg(
                tripCalculationLinkId: $linkId,
                mobilityId: $mobilityId,
                distanceKm: 50.0,
                nights: 1,
                mobilityEmissions: new Emissions(co2eq: '5'),
            ),
        ]);

        $this->assertEqualsWithDelta(150.0, $aggregation->kmByMobilityId[$mobilityId], 0.001);
        $this->assertSame(5.0, $aggregation->overnightStays);
        $this->assertSame(2, $aggregation->calculations);
        $this->assertEqualsWithDelta(15.0, (float) $aggregation->totalEmissions->co2eq(), 0.001);
        $this->assertEqualsWithDelta(7.5, (float) $aggregation->averageEmissions->co2eq(), 0.001);
    }

    public function testDoesNotExtrapolate(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();
        $mobilityId = (string) MobilityId::generate();

        $aggregation = $this->sut->aggregate([
            TripCalculationMother::singleLeg(
                tripCalculationLinkId: $linkId,
                mobilityId: $mobilityId,
                distanceKm: 100.0,
                trips: 2,
                roundTrip: true,
            ),
        ]);

        $this->assertEqualsWithDelta(400.0, $aggregation->kmByMobilityId[$mobilityId], 0.001);
    }
}
