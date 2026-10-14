<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Country;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;

class CountryMother
{
    public static function fromName(string $name): Country
    {
        return self::from(
            countryId: CountryId::generate(),
            name: $name,
        );
    }

    public static function fromNameAndId(string $name, string $id): Country
    {
        return self::from(
            countryId: new CountryId($id),
            name: $name,
        );
    }

    public static function fromNameAndIsoAndId(
        string $name,
        string $iso,
        string $id,
        ?float $vatRate = null,
        bool $europeanUnion = false,
    ): Country {
        return self::from(
            countryId: new CountryId($id),
            name: $name,
            iso: $iso,
            vatRate: $vatRate,
            europeanUnion: $europeanUnion,
        );
    }

    public static function spain(string $id = 'C-ES'): Country
    {
        return self::from(
            countryId: new CountryId($id),
            name: 'España',
            iso: 'ESP',
            vatRate: 21.0,
            europeanUnion: true,
        );
    }

    public static function from(
        CountryId $countryId,
        string $name = 'name',
        ?string $iso = null,
        ?float $vatRate = null,
        bool $europeanUnion = false,
    ): Country {
        $country = new Country(
            id: $countryId,
        );

        $country->register($name, $iso, vatRate: $vatRate, europeanUnion: $europeanUnion);

        return $country;
    }

    public static function random(): Country
    {
        return self::from(
            countryId: CountryId::generate(),
            name: 'country-' . random_int(100, 999),
            iso: 'ISO-' . random_int(100, 999),
        );
    }
}
