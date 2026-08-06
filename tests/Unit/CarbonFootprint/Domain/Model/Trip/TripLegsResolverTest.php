<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegChoice;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Geo\FixedRouteDistanceCalculator;
use PHPUnit\Framework\TestCase;

class TripLegsResolverTest extends TestCase
{
    private TripWaypoint $sevilla;

    private TripWaypoint $madrid;

    private TripWaypoint $venue;

    protected function setUp(): void
    {
        $this->sevilla = new TripWaypoint('sevilla', 'Sevilla, España', new GeoPoint(37.3891, -5.9845));
        $this->madrid = new TripWaypoint('madrid', 'Madrid, España', new GeoPoint(40.4168, -3.7038));
        $this->venue = new TripWaypoint('venue', 'IFEMA, Madrid', new GeoPoint(40.4680, -3.6160));
    }

    public function testASingleLegChainKeepsTheBehaviourOfASingleMeansOfTransport(): void
    {
        $mobilityId = MobilityId::generate();

        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->venue],
            [new TripLegChoice($mobilityId, TravelMode::Flight, 1)],
        );

        $this->assertCount(1, $legs);
        $this->assertSame(1, $legs[0]->position);
        $this->assertTrue($legs[0]->mobilityId->equals($mobilityId));
        $this->assertSame('sevilla', $legs[0]->origin->reference);
        $this->assertSame('venue', $legs[0]->destination->reference);
        $this->assertSame(DistanceSource::GreatCircle, $legs[0]->distanceSource);
        $this->assertGreaterThan(0.0, (float) $legs[0]->distanceKm);
    }

    public function testChainsAnIntermediateStopBetweenTwoMeansOfTransport(): void
    {
        $flightId = MobilityId::generate();
        $trainId = MobilityId::generate();

        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->madrid, $this->venue],
            [
                new TripLegChoice($flightId, TravelMode::Flight, 1),
                new TripLegChoice($trainId, TravelMode::Transit, 1),
            ],
        );

        $this->assertCount(2, $legs);
        $this->assertSame('sevilla', $legs[0]->origin->reference);
        $this->assertSame('madrid', $legs[0]->destination->reference);
        $this->assertSame('madrid', $legs[1]->origin->reference);
        $this->assertSame('venue', $legs[1]->destination->reference);
        $this->assertGreaterThan((float) $legs[1]->distanceKm, (float) $legs[0]->distanceKm);
    }

    public function testTheLastChoiceAlwaysEndsAtTheFinalPointOfTheChain(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->madrid, $this->venue],
            [new TripLegChoice(MobilityId::generate(), TravelMode::Driving, 1)],
        );

        $this->assertCount(1, $legs);
        $this->assertSame('venue', $legs[0]->destination->reference);
    }

    public function testFallsBackToTheManualDistanceWhenTheMeansHasNoTravelMode(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->madrid, $this->venue],
            [
                new TripLegChoice(MobilityId::generate(), null, 1, 500.0),
                new TripLegChoice(MobilityId::generate(), TravelMode::Transit, 1),
            ],
        );

        $this->assertSame(500.0, $legs[0]->distanceKm);
        $this->assertSame(DistanceSource::Manual, $legs[0]->distanceSource);
        $this->assertSame(DistanceSource::GreatCircle, $legs[1]->distanceSource);
    }

    public function testFallsBackToTheManualDistanceWhenAnIntermediateStopCouldNotBeResolved(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, new TripWaypoint(), $this->venue],
            [
                new TripLegChoice(MobilityId::generate(), TravelMode::Flight, 1, 400.0),
                new TripLegChoice(MobilityId::generate(), TravelMode::Transit, 1, 70.0),
            ],
        );

        $this->assertSame(400.0, $legs[0]->distanceKm);
        $this->assertSame(70.0, $legs[1]->distanceKm);
        $this->assertSame(DistanceSource::Manual, $legs[0]->distanceSource);
        $this->assertSame(DistanceSource::Manual, $legs[1]->distanceSource);
    }

    public function testPrefersARealRouteOverTheGreatCircleFallback(): void
    {
        $calculator = new FixedRouteDistanceCalculator();
        $calculator->set(new GeoPoint(37.3891, -5.9845), new GeoPoint(40.4680, -3.6160), TravelMode::Driving, 530.0);

        $legs = (new TripLegsResolver($calculator))->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->venue],
            [new TripLegChoice(MobilityId::generate(), TravelMode::Driving, 1, 999.0)],
        );

        $this->assertSame(530.0, $legs[0]->distanceKm);
        $this->assertSame(DistanceSource::Route, $legs[0]->distanceSource);
    }

    public function testEstimatesTheDistanceWhenTheMeansHasNoTravelModeNorManualKilometers(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::Intercity,
            [$this->sevilla, $this->venue],
            [new TripLegChoice(MobilityId::generate(), null, 1)],
        );

        $this->assertSame(DistanceSource::GreatCircle, $legs[0]->distanceSource);
        $this->assertEqualsWithDelta(470.0, (float) $legs[0]->distanceKm, 30.0);
    }

    public function testFloorsARouteDistanceThatCollapsesToZero(): void
    {
        $calculator = new FixedRouteDistanceCalculator();
        $calculator->set(new GeoPoint(40.4680, -3.6160), new GeoPoint(40.4680, -3.6160), TravelMode::Walking, 0.0);

        $legs = (new TripLegsResolver($calculator))->resolve(
            LegKind::City,
            [$this->venue, $this->venue],
            [new TripLegChoice(MobilityId::generate(), TravelMode::Walking, 1)],
        );

        $this->assertSame(0.1, $legs[0]->distanceKm);
        $this->assertSame(DistanceSource::Route, $legs[0]->distanceSource);
    }

    public function testFloorsTheGreatCircleDistanceBetweenTwoPointsOfTheSameBlock(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::City,
            [$this->venue, $this->venue],
            [new TripLegChoice(MobilityId::generate(), TravelMode::Walking, 1)],
        );

        $this->assertSame(0.1, $legs[0]->distanceKm);
        $this->assertSame(DistanceSource::GreatCircle, $legs[0]->distanceSource);
    }

    public function testKeepsTheOccupantsOfEachLeg(): void
    {
        $legs = $this->resolver()->resolve(
            LegKind::City,
            [$this->madrid, $this->venue],
            [new TripLegChoice(MobilityId::generate(), TravelMode::Driving, 4)],
        );

        $this->assertSame(4, $legs[0]->vehicleOccupants);
        $this->assertSame(LegKind::City, $legs[0]->kind);
    }

    public function testCapsTheNumberOfLegs(): void
    {
        $choices = [];
        $waypoints = [$this->sevilla];

        for ($i = 0; $i < 6; ++$i) {
            $choices[] = new TripLegChoice(MobilityId::generate(), TravelMode::Driving, 1, 10.0);
            $waypoints[] = $this->madrid;
        }

        $legs = (new TripLegsResolver(new GreatCircleRouteDistanceCalculator(), maxLegs: 2))
            ->resolve(LegKind::Intercity, $waypoints, $choices);

        $this->assertCount(2, $legs);
    }

    public function testReturnsNoLegsWithoutChoices(): void
    {
        $this->assertSame([], $this->resolver()->resolve(LegKind::Intercity, [$this->sevilla, $this->venue], []));
    }

    private function resolver(): TripLegsResolver
    {
        return new TripLegsResolver(new GreatCircleRouteDistanceCalculator());
    }
}
