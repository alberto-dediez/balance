<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Geo;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;

class GreatCircleRouteDistanceCalculatorTest extends TestCase
{
    private GreatCircleRouteDistanceCalculator $sut;

    protected function setUp(): void
    {
        $this->sut = new GreatCircleRouteDistanceCalculator();
    }

    public function testAppliesTheDetourFactorOfTheTravelMode(): void
    {
        $madrid = new GeoPoint(40.4168, -3.7038);
        $barcelona = new GeoPoint(41.3874, 2.1686);
        $straightDistance = $madrid->greatCircleDistanceKm($barcelona);

        $distance = $this->sut->calculate($madrid, $barcelona, TravelMode::Driving);

        $this->assertEqualsWithDelta($straightDistance * 1.30, $distance->kilometers, 0.0001);
        $this->assertSame(DistanceSource::GreatCircle, $distance->source);
        $this->assertNull($distance->durationSeconds);
    }

    public function testFlightsUseTheSmallestDetourFactor(): void
    {
        $madrid = new GeoPoint(40.4168, -3.7038);
        $barcelona = new GeoPoint(41.3874, 2.1686);

        $flight = $this->sut->calculate($madrid, $barcelona, TravelMode::Flight);
        $driving = $this->sut->calculate($madrid, $barcelona, TravelMode::Driving);

        $this->assertLessThan($driving->kilometers, $flight->kilometers);
    }
}
