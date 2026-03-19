<?php

namespace App\Tests\Unit\Property\Domain\Model\Country;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;

class CountryMother
{
    public static function fromName(string $name)
    {
        return self::from(
            countryId: CountryId::generate(),
            name: $name
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            countryId: new CountryId($id),
            name: $name
        );
    }

    public static function fromNameAndIsoAndId(string $name, string $iso, string $id)
    {
        return self::from(
            countryId: new CountryId($id),
            name: $name,
            iso: $iso
        );
    }

    public static function from(CountryId $countryId, string $name = 'name', ?string $iso = null): Country
    {
        $country = new Country(
            id: $countryId
        );

        $country->register($name, $iso);

        return $country;
    }

    public static function random()
    {
        return self::from(
            countryId: CountryId::generate(),
            name: 'country-' . random_int(100, 999),
            iso: 'ISO-' . random_int(100, 999)
        );
    }
}