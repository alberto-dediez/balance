<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Security\Domain\Model\User\User;

final class MaterialActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterMaterialActivityService $registerMaterialActivity,
        private UpdateMaterialActivityService $updateMaterialActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Material;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerMaterialActivity)(new RegisterMaterialActivityRequest(
            materialId: new MaterialId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateMaterialActivity)(new UpdateMaterialActivityRequest(
            activityId: $activityId,
            materialId: new MaterialId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
