<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Security\Domain\Model\User\User;

final class RoadTransportActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterRoadTransportActivityService $registerRoadTransportActivity,
        private UpdateRoadTransportActivityService $updateRoadTransportActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RoadTransport;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerRoadTransportActivity)(new RegisterRoadTransportActivityRequest(
            roadTransportId: new RoadTransportId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateRoadTransportActivity)(new UpdateRoadTransportActivityRequest(
            activityId: $activityId,
            roadTransportId: new RoadTransportId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
