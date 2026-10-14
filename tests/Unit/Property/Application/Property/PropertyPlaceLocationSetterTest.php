<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property;

use App\Property\Application\Property\PropertyPlaceLocationSetter;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class PropertyPlaceLocationSetterTest extends TestCase
{
    private InMemoryPlaceFinder $placeFinder;

    private PropertyPlaceLocationSetter $sut;

    protected function setUp(): void
    {
        $this->placeFinder = new InMemoryPlaceFinder();
        $this->placeFinder->add('place-reference', 'Hotel Ritz, Madrid', 40.4180, -3.6940);

        $this->sut = new PropertyPlaceLocationSetter($this->placeFinder);
    }

    public function testReplacesTheCoordinatesWithTheOnesOfTheResolvedPlace(): void
    {
        $info = $this->sut->resolve(new PropertyInfo(
            latitude: '1.0',
            longitude: '2.0',
            placeReference: 'place-reference',
        ));

        $this->assertInstanceOf(PropertyInfo::class, $info);
        $this->assertSame('place-reference', $info->placeReference());
        $this->assertSame('Hotel Ritz, Madrid', $info->placeLabel());
        $this->assertSame('40.418', $info->latitude());
        $this->assertSame('-3.694', $info->longitude());
    }

    public function testKeepsTheRestOfTheInformation(): void
    {
        $info = $this->sut->resolve(new PropertyInfo(
            address: 'Plaza de la Lealtad, 5',
            postalCode: '28014',
            rooms: 167,
            placeReference: 'place-reference',
        ));

        $this->assertInstanceOf(PropertyInfo::class, $info);
        $this->assertSame('Plaza de la Lealtad, 5', $info->address());
        $this->assertSame('28014', $info->postalCode());
        $this->assertSame(167, $info->rooms());
    }

    public function testDoesNotResolveAgainWhenTheReferenceDidNotChange(): void
    {
        $current = $this->sut->resolve(new PropertyInfo(placeReference: 'place-reference'));

        $info = $this->sut->resolve(new PropertyInfo(latitude: '1.0', longitude: '2.0', placeReference: 'place-reference'), $current);

        $this->assertInstanceOf(PropertyInfo::class, $info);
        $this->assertSame('Hotel Ritz, Madrid', $info->placeLabel());
        $this->assertSame('1.0', $info->latitude());
        $this->assertSame(1, $this->placeFinder->resolveCalls());
    }

    public function testClearsThePlaceWhenTheReferenceIsEmptyAndKeepsTheCoordinates(): void
    {
        $current = $this->sut->resolve(new PropertyInfo(placeReference: 'place-reference'));

        $info = $this->sut->resolve(new PropertyInfo(latitude: '1.0', longitude: '2.0'), $current);

        $this->assertInstanceOf(PropertyInfo::class, $info);
        $this->assertNull($info->placeReference());
        $this->assertNull($info->placeLabel());
        $this->assertSame('1.0', $info->latitude());
        $this->assertSame('2.0', $info->longitude());
    }

    public function testReturnsNothingWhenThereIsNoInformation(): void
    {
        $this->assertNull($this->sut->resolve(null));
        $this->assertSame(0, $this->placeFinder->resolveCalls());
    }

    public function testThrowsWhenThePlaceCannotBeResolved(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->resolve(new PropertyInfo(placeReference: 'unknown-reference'));
    }
}
