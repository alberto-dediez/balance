<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Security\Domain\Model\User\User;

final class OvernightStayActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterOvernightStayActivityService $registerOvernightStayActivity,
        private UpdateOvernightStayActivityService $updateOvernightStayActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::OvernightStay;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerOvernightStayActivity)(new RegisterOvernightStayActivityRequest(
            overnightStayId: new OvernightStayId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateOvernightStayActivity)(new UpdateOvernightStayActivityRequest(
            activityId: $activityId,
            overnightStayId: new OvernightStayId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
