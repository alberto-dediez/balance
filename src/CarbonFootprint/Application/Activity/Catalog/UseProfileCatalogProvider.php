<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;

final class UseProfileCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private UseProfileRepository $useProfileRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::UseProfile;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->useProfileRepository->findByFilter(new UseProfileFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }
}
