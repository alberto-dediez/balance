<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\User;

final class WasteActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterWasteActivityService $registerWasteActivity,
        private UpdateWasteActivityService $updateWasteActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Waste;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerWasteActivity)(new RegisterWasteActivityRequest(
            wasteId: new WasteId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateWasteActivity)(new UpdateWasteActivityRequest(
            activityId: $activityId,
            wasteId: new WasteId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
