<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Property\Domain\Model\Country\CountryId;

class ElectricityMixMother
{
    public static function registeredElectricityMix(): ElectricityMix
    {
        return self::fromCountry((string) CountryId::generate());
    }

    public static function fromCountry(string $countryId): ElectricityMix
    {
        return self::from(
            id: (string) ElectricityMixId::generate(),
            countryId: $countryId,
        );
    }

    public static function fromCountryAndId(string $countryId, string $id): ElectricityMix
    {
        return self::from(
            id: $id,
            countryId: $countryId,
        );
    }

    public static function from(
        string $id,
        string $countryId,
    ): ElectricityMix {
        $electricityMix = new ElectricityMix(
            id: new ElectricityMixId($id),
        );
        $electricityMix->register(new CountryId($countryId));

        return $electricityMix;
    }

    public static function random(): ElectricityMix
    {
        return self::from(
            id: (string) ElectricityMixId::generate(),
            countryId: (string) CountryId::generate(),
        );
    }
}
