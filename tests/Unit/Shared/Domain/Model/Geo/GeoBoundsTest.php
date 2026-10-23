<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Geo;

use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;

class GeoBoundsTest extends TestCase
{
    public function testContainsAPointInsideTheRectangle(): void
    {
        $bounds = new GeoBounds(new GeoPoint(37.8, -1.5), new GeoPoint(40.8, 0.7));

        $this->assertTrue($bounds->contains(new GeoPoint(39.47, -0.37)));
    }

    public function testDoesNotContainAPointOutsideByLatitude(): void
    {
        $bounds = new GeoBounds(new GeoPoint(37.8, -1.5), new GeoPoint(40.8, 0.7));

        $this->assertFalse($bounds->contains(new GeoPoint(41.38, -0.37)));
    }

    public function testDoesNotContainAPointOutsideByLongitude(): void
    {
        $bounds = new GeoBounds(new GeoPoint(37.8, -1.5), new GeoPoint(40.8, 0.7));

        $this->assertFalse($bounds->contains(new GeoPoint(39.47, -3.7)));
    }

    public function testSupportsRectanglesCrossingTheAntimeridian(): void
    {
        $bounds = new GeoBounds(new GeoPoint(-20.0, 170.0), new GeoPoint(-10.0, -170.0));

        $this->assertTrue($bounds->contains(new GeoPoint(-15.0, 175.0)));
        $this->assertTrue($bounds->contains(new GeoPoint(-15.0, -175.0)));
        $this->assertFalse($bounds->contains(new GeoPoint(-15.0, 0.0)));
    }
}
