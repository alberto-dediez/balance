<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Property\Domain\Model\Country\CountryId;

final class ElectricityMixEmissionsResolver
{
    public function __construct(
        private readonly ElectricityMixRepository $electricityMixRepository,
        private readonly EmissionsCalculator $emissionsCalculator,
    ) {}

    public function resolve(
        ?CountryId $countryId,
        int $year,
        EmissionFactorGroupId $emissionFactorGroupId,
        Quantity $quantity,
    ): ?ElectricityMixEmissions {
        if (null === $countryId) {
            return null;
        }

        $electricityMix = $this->electricityMixRepository->findByCountryId($countryId);

        if (null === $electricityMix) {
            return null;
        }

        $emissionFactor = $electricityMix->emissionFactorFor($year, $emissionFactorGroupId);

        if (null === $emissionFactor) {
            return null;
        }

        return new ElectricityMixEmissions(
            electricityMixId: $electricityMix->id(),
            emissionFactor: $emissionFactor,
            emissions: $this->emissionsCalculator->calculate($emissionFactor, $quantity),
        );
    }
}
