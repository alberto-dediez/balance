<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class CombustibleCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private CombustibleRepository $combustibleRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Combustible;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->combustibleRepository->findByFilter(new CombustibleFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(): array
    {
        return [];
    }
}
