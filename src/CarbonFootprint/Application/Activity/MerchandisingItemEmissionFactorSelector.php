<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;

final class MerchandisingItemEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private MerchandisingItemRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new MerchandisingItemId($itemId);
        $merchandisingItem = $this->repository->find($id);
        if (null === $merchandisingItem) {
            throw new \InvalidArgumentException(sprintf('MerchandisingItem "%s" not found.', $id));
        }
        $factor = $merchandisingItem->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for merchandising item "%s" in year %d.', $id, $year));
        }

        return ItemCondition::Recycled === $condition ? $factor->recycledFactor() : $factor->newFactor();
    }
}
