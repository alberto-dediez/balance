<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MaterialAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class MaterialAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterMaterialActivityService $registerMaterialActivity,
        private UpdateMaterialActivityService $updateMaterialActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Material;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user): ActivityId
    {
        assert($answer instanceof MaterialAnswer);

        $response = ($this->registerMaterialActivity)(new RegisterMaterialActivityRequest(
            accountId: $accountId,
            materialId: $answer->materialId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof MaterialAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateMaterialActivity)(new UpdateMaterialActivityRequest(
            activityId: $answer->existingActivityId,
            materialId: $answer->materialId,
            condition: $answer->condition,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
