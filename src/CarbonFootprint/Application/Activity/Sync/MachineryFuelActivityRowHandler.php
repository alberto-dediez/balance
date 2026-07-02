<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Security\Domain\Model\User\User;

final class MachineryFuelActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterMachineryFuelActivityService $registerMachineryFuelActivity,
        private UpdateMachineryFuelActivityService $updateMachineryFuelActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MachineryFuel;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerMachineryFuelActivity)(new RegisterMachineryFuelActivityRequest(
            machineryFuelId: new MachineryFuelId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateMachineryFuelActivity)(new UpdateMachineryFuelActivityRequest(
            activityId: $activityId,
            machineryFuelId: new MachineryFuelId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
