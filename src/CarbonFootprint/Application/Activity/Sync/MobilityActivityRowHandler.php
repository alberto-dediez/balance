<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Security\Domain\Model\User\User;

final class MobilityActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterMobilityActivityService $registerMobilityActivity,
        private UpdateMobilityActivityService $updateMobilityActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Mobility;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerMobilityActivity)(new RegisterMobilityActivityRequest(
            mobilityId: new MobilityId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateMobilityActivity)(new UpdateMobilityActivityRequest(
            activityId: $activityId,
            mobilityId: new MobilityId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
