<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution;

use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryRepository;

final class ContributionCountries
{
    public function __construct(
        private CountryRepository $countryRepository,
    ) {}

    /** @return array<string, string> */
    public function countries(): array
    {
        $countries = [];

        foreach ($this->countryRepository->findByFilter(new CountryFilter(limit: 0)) as $country) {
            $iso = strtoupper(trim((string) $country->iso()));

            if ('' !== $iso) {
                $countries[$iso] = $country->name();
            }
        }

        asort($countries);

        return $countries;
    }
}
