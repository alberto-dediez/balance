<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Security\Domain\Model\User\User;

final class MerchandisingItemActivityRowHandler implements ActivityRowHandler
{
    public function __construct(
        private RegisterMerchandisingItemActivityService $registerMerchandisingItemActivity,
        private UpdateMerchandisingItemActivityService $updateMerchandisingItemActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function register(ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        $response = ($this->registerMerchandisingItemActivity)(new RegisterMerchandisingItemActivityRequest(
            merchandisingItemId: new MerchandisingItemId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityId $activityId, ActivityRow $row, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        ($this->updateMerchandisingItemActivity)(new UpdateMerchandisingItemActivityRequest(
            activityId: $activityId,
            merchandisingItemId: new MerchandisingItemId($row->itemId),
            condition: $row->condition,
            quantity: $row->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
