<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMerchandisingItemActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMerchandisingItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MerchandisingItemAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\User\User;

final class MerchandisingItemAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterMerchandisingItemActivityService $registerMerchandisingItemActivity,
        private UpdateMerchandisingItemActivityService $updateMerchandisingItemActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        assert($answer instanceof MerchandisingItemAnswer);

        $response = ($this->registerMerchandisingItemActivity)(new RegisterMerchandisingItemActivityRequest(
            merchandisingItemId: $answer->merchandisingItemId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof MerchandisingItemAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateMerchandisingItemActivity)(new UpdateMerchandisingItemActivityRequest(
            activityId: $answer->existingActivityId,
            merchandisingItemId: $answer->merchandisingItemId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
