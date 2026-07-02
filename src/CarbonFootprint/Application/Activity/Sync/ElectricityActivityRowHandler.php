<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Security\Domain\Model\User\User;

final class ElectricityActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterElectricityActivityService $registerElectricityActivity,
        private UpdateElectricityActivityService $updateElectricityActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $gdOFactorId = $row->secondaryItemId ?? throw new \InvalidArgumentException('Electricity activity row requires a GdO factor id.');

        $response = ($this->registerElectricityActivity)(new RegisterElectricityActivityRequest(
            electricityMarketerId: new ElectricityMarketerId($row->itemId),
            gdOFactorId: new GdOFactorId($gdOFactorId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        $gdOFactorId = $row->secondaryItemId ?? throw new \InvalidArgumentException('Electricity activity row requires a GdO factor id.');

        ($this->updateElectricityActivity)(new UpdateElectricityActivityRequest(
            activityId: $activityId,
            electricityMarketerId: new ElectricityMarketerId($row->itemId),
            gdOFactorId: new GdOFactorId($gdOFactorId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
