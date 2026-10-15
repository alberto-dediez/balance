<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;

final class PurchaseServiceEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private PurchaseServiceRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::PurchaseService;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new PurchaseServiceId($itemId);
        $purchaseService = $this->repository->find($id);
        if (null === $purchaseService) {
            throw new \InvalidArgumentException(sprintf('PurchaseService "%s" not found.', $id));
        }
        $factor = $purchaseService->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for purchase service "%s" in year %d.', $id, $year));
        }

        return $factor->emissionFactor();
    }
}
