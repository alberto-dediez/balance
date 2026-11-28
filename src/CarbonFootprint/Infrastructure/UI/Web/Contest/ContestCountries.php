<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Contest;

use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCountries;
use App\Shared\Application\Place\CountryRegionCode;

final class ContestCountries
{
    public function __construct(
        private ContributionCountries $contributionCountries,
        private CountryRegionCode $countryRegionCode,
    ) {}

    /** @return array<string, string> */
    public function countries(): array
    {
        $countries = [];

        foreach ($this->contributionCountries->countries() as $iso => $name) {
            $alpha2 = $this->alpha2($iso);

            if (null !== $alpha2 && !isset($countries[$alpha2])) {
                $countries[$alpha2] = $name;
            }
        }

        asort($countries);

        return $countries;
    }

    public function alpha2(string $iso): ?string
    {
        $iso = strtoupper(trim($iso));

        if (1 === preg_match('/^[A-Z]{2}$/', $iso)) {
            return $iso;
        }

        $alpha2 = '' === $iso ? null : $this->countryRegionCode->alpha2($iso);

        return null === $alpha2 ? null : strtoupper($alpha2);
    }
}
