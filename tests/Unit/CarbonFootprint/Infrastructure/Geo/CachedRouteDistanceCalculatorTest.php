<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Geo;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Infrastructure\Geo\CachedRouteDistanceCalculator;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Geo\FixedRouteDistanceCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CachedRouteDistanceCalculatorTest extends TestCase
{
    private GeoPoint $madrid;

    private GeoPoint $barcelona;

    protected function setUp(): void
    {
        $this->madrid = new GeoPoint(40.4168, -3.7038);
        $this->barcelona = new GeoPoint(41.3874, 2.1686);
    }

    public function testCallsTheInnerCalculatorOnlyOncePerRoute(): void
    {
        $inner = new FixedRouteDistanceCalculator();
        $inner->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);

        $sut = new CachedRouteDistanceCalculator($inner, new ArrayAdapter());

        $first = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);
        $second = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame(620.0, $second->kilometers);
        $this->assertSame(1, $inner->calls());
    }

    public function testDifferentTravelModesAreCachedSeparately(): void
    {
        $inner = new FixedRouteDistanceCalculator();
        $inner->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);
        $inner->set($this->madrid, $this->barcelona, TravelMode::Transit, 680.0);

        $sut = new CachedRouteDistanceCalculator($inner, new ArrayAdapter());

        $driving = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);
        $transit = $sut->calculate($this->madrid, $this->barcelona, TravelMode::Transit);

        $this->assertNotNull($driving);
        $this->assertNotNull($transit);
        $this->assertSame(620.0, $driving->kilometers);
        $this->assertSame(680.0, $transit->kilometers);
        $this->assertSame(2, $inner->calls());
    }

    public function testCoordinatesWithinAMeterShareTheSameCacheEntry(): void
    {
        $inner = new FixedRouteDistanceCalculator();
        $inner->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);

        $sut = new CachedRouteDistanceCalculator($inner, new ArrayAdapter());

        $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);
        $nearby = $sut->calculate(new GeoPoint(40.4168004, -3.7037996), $this->barcelona, TravelMode::Driving);

        $this->assertNotNull($nearby);
        $this->assertSame(620.0, $nearby->kilometers);
        $this->assertSame(1, $inner->calls());
    }

    public function testPlacesOnTheSameStreetDoNotShareTheSameCacheEntry(): void
    {
        $inner = new FixedRouteDistanceCalculator();
        $inner->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);

        $sut = new CachedRouteDistanceCalculator($inner, new ArrayAdapter());

        $sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving);
        $otherPlace = $sut->calculate(new GeoPoint(40.4173, -3.7042), $this->barcelona, TravelMode::Driving);

        $this->assertNull($otherPlace);
        $this->assertSame(2, $inner->calls());
    }

    public function testARealRouteIsCachedForALongTime(): void
    {
        $inner = new FixedRouteDistanceCalculator();
        $inner->set($this->madrid, $this->barcelona, TravelMode::Driving, 620.0);
        $cache = new ArrayAdapter();

        (new CachedRouteDistanceCalculator($inner, $cache))->calculate($this->madrid, $this->barcelona, TravelMode::Driving);

        $this->assertGreaterThan(86400, $this->cachedSecondsToLive($cache));
    }

    public function testAFailedRouteIsCachedForAShortTime(): void
    {
        $inner = new FixedRouteDistanceCalculator(alwaysFails: true);
        $cache = new ArrayAdapter();

        $sut = new CachedRouteDistanceCalculator($inner, $cache);

        $this->assertNull($sut->calculate($this->madrid, $this->barcelona, TravelMode::Driving));
        $this->assertLessThanOrEqual(3600, $this->cachedSecondsToLive($cache));
    }

    private function cachedSecondsToLive(ArrayAdapter $cache): float
    {
        $keys = array_keys($cache->getValues());

        $this->assertCount(1, $keys);

        $metadata = $cache->getItem((string) $keys[0])->getMetadata();

        $this->assertArrayHasKey(ItemInterface::METADATA_EXPIRY, $metadata);

        return ((float) $metadata[ItemInterface::METADATA_EXPIRY]) - microtime(true);
    }
}
