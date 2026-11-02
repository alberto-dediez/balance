<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;

class CompanyIntensityDenominatorsDefaults
{
    public function __construct(
        private CompanyProperties $companyProperties,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
    ) {}

    public function for(CompanyCarbonFootprint $companyCarbonFootprint): IntensityDenominators
    {
        $denominators = [];

        foreach ($this->companyProperties->consolidated($companyCarbonFootprint->companyId()) as $property) {
            $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->findByPropertyIdAndYear($property->id(), $companyCarbonFootprint->year());

            if (null === $propertyCarbonFootprint || $propertyCarbonFootprint->isRemoved()) {
                continue;
            }

            $denominators[] = $propertyCarbonFootprint->intensityDenominators();
        }

        return IntensityDenominators::sum($denominators);
    }
}
