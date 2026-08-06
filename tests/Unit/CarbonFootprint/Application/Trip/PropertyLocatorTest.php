<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class PropertyLocatorTest extends TestCase
{
    public function testLocatesAPropertyWithCoordinates(): void
    {
        $point = $this->createSUT()->locate($this->propertyWith(new PropertyInfo(longitude: '-3.6160', latitude: '40.4680')));

        $this->assertNotNull($point);
        $this->assertEqualsWithDelta(40.4680, $point->latitude, 0.0001);
        $this->assertEqualsWithDelta(-3.6160, $point->longitude, 0.0001);
    }

    public function testTrimsTheStoredCoordinates(): void
    {
        $point = $this->createSUT()->locate($this->propertyWith(new PropertyInfo(longitude: ' -3.6160 ', latitude: ' 40.4680 ')));

        $this->assertNotNull($point);
        $this->assertEqualsWithDelta(40.4680, $point->latitude, 0.0001);
        $this->assertEqualsWithDelta(-3.6160, $point->longitude, 0.0001);
    }

    public function testDoesNotLocateAMissingProperty(): void
    {
        $this->assertNull($this->createSUT()->locate(null));
    }

    public function testDoesNotLocateAPropertyWithoutCoordinates(): void
    {
        $this->assertNull($this->createSUT()->locate($this->propertyWith(new PropertyInfo())));
    }

    public function testDoesNotLocateAPropertyWithOnlyOneCoordinate(): void
    {
        $this->assertNull($this->createSUT()->locate($this->propertyWith(new PropertyInfo(latitude: '40.4680'))));
        $this->assertNull($this->createSUT()->locate($this->propertyWith(new PropertyInfo(longitude: '-3.6160'))));
    }

    public function testDoesNotLocateAPropertyWithNonNumericCoordinates(): void
    {
        $this->assertNull($this->createSUT()->locate($this->propertyWith(new PropertyInfo(longitude: 'oeste', latitude: '40.4680'))));
        $this->assertNull($this->createSUT()->locate($this->propertyWith(new PropertyInfo(longitude: '-3.6160', latitude: ''))));
    }

    private function propertyWith(PropertyInfo $info): Property
    {
        return PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            accountId: null,
            info: $info,
        );
    }

    private function createSUT(): PropertyLocator
    {
        return new PropertyLocator();
    }
}
