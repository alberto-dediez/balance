<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class EconomicSectorEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private EconomicSectorRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::EconomicSector;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new EconomicSectorId($itemId);
        $economicSector = $this->repository->find($id);
        if (null === $economicSector) {
            throw new \InvalidArgumentException(sprintf('EconomicSector "%s" not found.', $id));
        }
        $factor = $economicSector->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for economic sector "%s" in year %d.', $id, $year));
        }

        return $factor->emissionFactor();
    }
}
