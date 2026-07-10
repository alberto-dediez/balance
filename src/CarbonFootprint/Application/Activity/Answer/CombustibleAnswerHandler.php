<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCombustibleActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCombustibleActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class CombustibleAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterCombustibleActivityService $registerCombustibleActivity,
        private UpdateCombustibleActivityService $updateCombustibleActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Combustible;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user): ActivityId
    {
        assert($answer instanceof CombustibleAnswer);

        $response = ($this->registerCombustibleActivity)(new RegisterCombustibleActivityRequest(
            accountId: $accountId,
            combustibleId: $answer->combustibleId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof CombustibleAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateCombustibleActivity)(new UpdateCombustibleActivityRequest(
            activityId: $answer->existingActivityId,
            combustibleId: $answer->combustibleId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);
    }
}
