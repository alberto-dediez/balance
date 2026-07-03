<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;

final class MerchandisingItemCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private MerchandisingItemRepository $merchandisingItemRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->merchandisingItemRepository->findByFilter(new MerchandisingItemFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(): array
    {
        return [];
    }
}
