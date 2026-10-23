<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Geo;

use App\Shared\Application\Place\CountryRegionCode;
use Symfony\Component\Intl\Countries;

final class IntlCountryRegionCode implements CountryRegionCode
{
    public function alpha2(string $isoCode): ?string
    {
        $isoCode = strtoupper(trim($isoCode));

        return match (strlen($isoCode)) {
            2 => Countries::exists($isoCode) ? $isoCode : null,
            3 => Countries::alpha3CodeExists($isoCode) ? Countries::getAlpha2Code($isoCode) : null,
            default => null,
        };
    }
}
