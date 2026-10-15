<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;

final class PurchaseServiceCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private PurchaseServiceRepository $purchaseServiceRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::PurchaseService;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->purchaseServiceRepository->findByFilter(new PurchaseServiceFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }
}
