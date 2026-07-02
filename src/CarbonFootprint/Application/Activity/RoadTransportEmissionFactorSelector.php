<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;

final class RoadTransportEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private RoadTransportRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RoadTransport;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new RoadTransportId($itemId);
        $roadTransport = $this->repository->find($id);
        if (null === $roadTransport) {
            throw new \InvalidArgumentException(sprintf('RoadTransport "%s" not found.', $id));
        }
        $factor = $roadTransport->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for road transport "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
