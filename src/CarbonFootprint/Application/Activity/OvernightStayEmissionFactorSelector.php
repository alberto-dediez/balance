<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;

final class OvernightStayEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private OvernightStayRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::OvernightStay;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new OvernightStayId($itemId);
        $overnightStay = $this->repository->find($id);
        if (null === $overnightStay) {
            throw new \InvalidArgumentException(sprintf('OvernightStay "%s" not found.', $id));
        }
        $factor = $overnightStay->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for overnight stay "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
