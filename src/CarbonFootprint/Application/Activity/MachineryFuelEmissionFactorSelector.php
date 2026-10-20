<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelEmissionFactor;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;

final class MachineryFuelEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private MachineryFuelRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MachineryFuel;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        return $this->emissionFactorRowFor($itemId, $year, $groupId)->emissionFactor();
    }

    public function selectWellToTank(string $itemId, int $year, EmissionFactorGroupId $groupId): EmissionFactor
    {
        return $this->emissionFactorRowFor($itemId, $year, $groupId)->wellToTankFactor();
    }

    private function emissionFactorRowFor(string $itemId, int $year, EmissionFactorGroupId $groupId): MachineryFuelEmissionFactor
    {
        $id = new MachineryFuelId($itemId);
        $machineryFuel = $this->repository->find($id);
        if (null === $machineryFuel) {
            throw new \InvalidArgumentException(sprintf('MachineryFuel "%s" not found.', $id));
        }
        $row = $machineryFuel->emissionFactors()->forYearAndGroup($year, $groupId);
        if (null === $row) {
            throw new \InvalidArgumentException(sprintf('No emission factor for machinery fuel "%s" in year %d.', $id, $year));
        }

        return $row;
    }
}
