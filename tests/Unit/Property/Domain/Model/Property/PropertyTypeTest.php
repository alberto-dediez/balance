<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Property\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{PropertyType}>
     */
    public static function accommodationTypes(): iterable
    {
        yield 'hotel' => [PropertyType::Hotel];
        yield 'aparthotel' => [PropertyType::Aparthotel];
        yield 'vacational hotel' => [PropertyType::VacationalHotel];
        yield 'resort hotel' => [PropertyType::ResortHotel];
        yield 'business hotel' => [PropertyType::BusinessHotel];
        yield 'spa hotel' => [PropertyType::SpaHotel];
        yield 'rural accommodation' => [PropertyType::RuralAccommodation];
        yield 'camping' => [PropertyType::Camping];
        yield 'city hotel' => [PropertyType::CityHotel];
        yield 'flat' => [PropertyType::Flat];
        yield 'hostel' => [PropertyType::Hostel];
        yield 'apartment' => [PropertyType::Apartment];
        yield 'motel' => [PropertyType::Motel];
    }

    public function testOtherIsNotAnAccommodation(): void
    {
        $this->assertFalse(PropertyType::Other->isAccommodation());
    }

    /** @dataProvider accommodationTypes */
    public function testIsAnAccommodation(PropertyType $propertyType): void
    {
        $this->assertTrue($propertyType->isAccommodation());
    }

    public function testEveryTypeIsClassified(): void
    {
        $classified = [PropertyType::Other];

        foreach (self::accommodationTypes() as [$propertyType]) {
            $classified[] = $propertyType;
        }

        $this->assertEqualsCanonicalizing(
            array_map(static fn(PropertyType $propertyType): string => $propertyType->value, PropertyType::cases()),
            array_map(static fn(PropertyType $propertyType): string => $propertyType->value, $classified),
        );
    }
}
