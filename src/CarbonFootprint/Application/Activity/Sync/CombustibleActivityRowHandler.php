<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\User\User;

final class CombustibleActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterCombustibleActivityService $registerCombustibleActivity,
        private UpdateCombustibleActivityService $updateCombustibleActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Combustible;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerCombustibleActivity)(new RegisterCombustibleActivityRequest(
            combustibleId: new CombustibleId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateCombustibleActivity)(new UpdateCombustibleActivityRequest(
            activityId: $activityId,
            combustibleId: new CombustibleId($row->itemId),
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
