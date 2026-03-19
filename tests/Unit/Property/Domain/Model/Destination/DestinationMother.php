<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;

class DestinationMother
{
    public static function fromName(string $name)
    {
        return self::from(
            destinationId: DestinationId::generate(),
            name: $name,
        );
    }

    public static function fromNameAndCountry(string $name, string $countryId)
    {
        return self::from(
            destinationId: DestinationId::generate(),
            name: $name,
            countryId: $countryId,
        );
    }

    public static function fromNameAndId(string $name, string $id)
    {
        return self::from(
            destinationId: new DestinationId($id),
            name: $name,
        );
    }

    public static function fromNameAndCountryAndId(string $name, string $countryId, string $id)
    {
        return self::from(
            destinationId: new DestinationId($id),
            name: $name,
            countryId: $countryId,
        );
    }

    public static function from(DestinationId $destinationId, string $name = 'name', ?string $countryId = null): Destination
    {
        $destination = new Destination(
            id: $destinationId,
        );

        $destination->register($name, null !== $countryId ? new CountryId($countryId) : CountryId::generate());

        return $destination;
    }

    public static function random()
    {
        return self::from(
            destinationId: DestinationId::generate(),
            name: 'destination-' . random_int(100, 999),
        );
    }
}
