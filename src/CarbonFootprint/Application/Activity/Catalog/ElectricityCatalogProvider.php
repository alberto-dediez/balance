<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;

final class ElectricityCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private GdOFactorRepository $gdOFactorRepository,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->electricityMarketerRepository->findByFilter(new ElectricityMarketerFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(): array
    {
        return array_values($this->gdOFactorRepository->findByFilter(new GdOFactorFilter(limit: 0)));
    }
}
