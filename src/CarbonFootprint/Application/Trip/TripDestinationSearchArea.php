<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Application\Place\CountryRegionCode;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;

final class TripDestinationSearchArea
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly CountryRegionCode $countryRegionCode,
    ) {}

    public function forLink(TripCalculationLink $tripCalculationLink): ?PlaceSearchArea
    {
        return match ($tripCalculationLink->destinationMode()) {
            TripDestinationMode::Countries => new PlaceSearchArea(regionCodes: array_map(
                static fn(string $regionCode): string => strtolower($regionCode),
                $this->regionCodesOf($tripCalculationLink),
            )),
            TripDestinationMode::Region => new PlaceSearchArea(bounds: $tripCalculationLink->destinationRegionBounds()),
            default => null,
        };
    }

    /** @return list<string> */
    private function regionCodesOf(TripCalculationLink $tripCalculationLink): array
    {
        $regionCodes = [];

        foreach ($tripCalculationLink->destinationCountryIds() as $countryId) {
            $iso = $this->countryRepository->find($countryId)?->iso();
            $regionCode = null !== $iso ? $this->countryRegionCode->alpha2($iso) : null;

            if (null !== $regionCode) {
                $regionCodes[] = $regionCode;
            }
        }

        return array_values(array_unique($regionCodes));
    }
}
