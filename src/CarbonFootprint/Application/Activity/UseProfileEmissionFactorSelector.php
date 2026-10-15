<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;

final class UseProfileEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private UseProfileRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::UseProfile;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new UseProfileId($itemId);
        $useProfile = $this->repository->find($id);
        if (null === $useProfile) {
            throw new \InvalidArgumentException(sprintf('UseProfile "%s" not found.', $id));
        }
        $factor = $useProfile->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for use profile "%s" in year %d.', $id, $year));
        }

        return $factor->emissionFactor();
    }
}
