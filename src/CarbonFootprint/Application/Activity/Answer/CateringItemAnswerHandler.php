<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCateringItemActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCateringItemActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CateringItemAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class CateringItemAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterCateringItemActivityService $registerCateringItemActivity,
        private UpdateCateringItemActivityService $updateCateringItemActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CateringItem;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user): ActivityId
    {
        assert($answer instanceof CateringItemAnswer);

        $response = ($this->registerCateringItemActivity)(new RegisterCateringItemActivityRequest(
            accountId: $accountId,
            cateringItemId: $answer->cateringItemId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof CateringItemAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateCateringItemActivity)(new UpdateCateringItemActivityRequest(
            activityId: $answer->existingActivityId,
            cateringItemId: $answer->cateringItemId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
