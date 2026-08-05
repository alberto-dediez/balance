<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Geo;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Infrastructure\Geo\FallbackRouteDistanceCalculator;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Geo\FixedRouteDistanceCalculator;
use PHPUnit\Framework\TestCase;

class FallbackRouteDistanceCalculatorTest extends TestCase
{
    private GeoPoint $madrid;

    private GeoPoint $barcelona;

    protected function setUp(): void
    {
        $this->madrid = new GeoPoint(40.4168, -3.7038);
        $this->barcelona = new GeoPoint(41.3874, 2.1686);
    }

    public function testUsesThePrimaryCalculatorWhenItAnswers(): void
    {
        $primary = new FixedRouteDistanceCalculator();
        $primary->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);

        $sut = new FallbackRouteDistanceCalculator($primary, new GreatCircleRouteDistanceCalculator());
        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertSame(620.0, $distance->kilometers);
        $this->assertSame(DistanceSource::Route, $distance->source);
    }

    public function testFallsBackToGreatCircleWhenThePrimaryFails(): void
    {
        $sut = new FallbackRouteDistanceCalculator(new FixedRouteDistanceCalculator(alwaysFails: true), new GreatCircleRouteDistanceCalculator());
        $distance = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertSame(DistanceSource::GreatCircle, $distance->source);
        $this->assertGreaterThan(0.0, $distance->kilometers);
    }
}
