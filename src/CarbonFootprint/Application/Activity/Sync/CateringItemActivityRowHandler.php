<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\User\User;

final class CateringItemActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterCateringItemActivityService $registerCateringItemActivity,
        private UpdateCateringItemActivityService $updateCateringItemActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CateringItem;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerCateringItemActivity)(new RegisterCateringItemActivityRequest(
            cateringItemId: new CateringItemId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateCateringItemActivity)(new UpdateCateringItemActivityRequest(
            activityId: $activityId,
            cateringItemId: new CateringItemId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
