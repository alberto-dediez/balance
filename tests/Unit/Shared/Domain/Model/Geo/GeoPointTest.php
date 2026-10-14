<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;

class GeoPointTest extends TestCase
{
    public function testCalculatesGreatCircleDistanceBetweenMadridAndBarcelona(): void
    {
        $madrid = new GeoPoint(40.4168, -3.7038);
        $barcelona = new GeoPoint(41.3874, 2.1686);

        $this->assertEqualsWithDelta(505.0, $madrid->greatCircleDistanceKm($barcelona), 10.0);
    }

    public function testDistanceIsSymmetric(): void
    {
        $madrid = new GeoPoint(40.4168, -3.7038);
        $sevilla = new GeoPoint(37.3891, -5.9845);

        $this->assertEqualsWithDelta(
            $madrid->greatCircleDistanceKm($sevilla),
            $sevilla->greatCircleDistanceKm($madrid),
            0.0001,
        );
    }

    public function testDistanceToItselfIsZero(): void
    {
        $madrid = new GeoPoint(40.4168, -3.7038);

        $this->assertSame(0.0, $madrid->greatCircleDistanceKm($madrid));
    }

    public function testEqualsComparesCoordinates(): void
    {
        $this->assertTrue((new GeoPoint(40.4168, -3.7038))->equals(new GeoPoint(40.4168, -3.7038)));
        $this->assertFalse((new GeoPoint(40.4168, -3.7038))->equals(new GeoPoint(41.3874, 2.1686)));
    }
}
