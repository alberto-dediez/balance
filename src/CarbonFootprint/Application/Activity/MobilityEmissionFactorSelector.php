<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

final class MobilityEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private MobilityRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Mobility;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new MobilityId($itemId);
        $mobility = $this->repository->find($id);
        if (null === $mobility) {
            throw new \InvalidArgumentException(sprintf('Mobility "%s" not found.', $id));
        }
        $factor = $mobility->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for mobility "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
