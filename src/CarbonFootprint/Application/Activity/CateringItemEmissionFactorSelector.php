<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class CateringItemEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private CateringItemRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CateringItem;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId): EmissionFactor
    {
        $id = new CateringItemId($itemId);
        $cateringItem = $this->repository->find($id);
        if (null === $cateringItem) {
            throw new \InvalidArgumentException(sprintf('CateringItem "%s" not found.', $id));
        }
        $factor = $cateringItem->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for catering item "%s" in year %d.', $id, $year));
        }

        return ItemCondition::Recycled === $condition ? $factor->recycledFactor() : $factor->newFactor();
    }
}
