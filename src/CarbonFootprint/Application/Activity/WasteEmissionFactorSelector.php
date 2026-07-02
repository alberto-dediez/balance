<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;

final class WasteEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private WasteRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Waste;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new WasteId($itemId);
        $waste = $this->repository->find($id);
        if (null === $waste) {
            throw new \InvalidArgumentException(sprintf('Waste "%s" not found.', $id));
        }
        $factor = $waste->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for waste "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
