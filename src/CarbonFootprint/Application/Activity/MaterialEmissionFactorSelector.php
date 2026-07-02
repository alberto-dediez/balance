<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;

final class MaterialEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private MaterialRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Material;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId): EmissionFactor
    {
        $id = new MaterialId($itemId);
        $material = $this->repository->find($id);
        if (null === $material) {
            throw new \InvalidArgumentException(sprintf('Material "%s" not found.', $id));
        }
        $factor = $material->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for material "%s" in year %d.', $id, $year));
        }

        return ItemCondition::Recycled === $condition ? $factor->recycledFactor() : $factor->newFactor();
    }
}
