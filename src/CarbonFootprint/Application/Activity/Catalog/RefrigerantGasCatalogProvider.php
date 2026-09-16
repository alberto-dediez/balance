<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;

final class RefrigerantGasCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private RefrigerantGasRepository $refrigerantGasRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RefrigerantGas;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->refrigerantGasRepository->findByFilter(new RefrigerantGasFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }
}
