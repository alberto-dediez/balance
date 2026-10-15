<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterProcessActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterProcessActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateProcessActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateProcessActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ProcessAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class ProcessAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterProcessActivityService $registerProcessActivity,
        private UpdateProcessActivityService $updateProcessActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Process;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof ProcessAnswer);

        $response = ($this->registerProcessActivity)(new RegisterProcessActivityRequest(
            accountId: $accountId,
            processId: $answer->processId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof ProcessAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateProcessActivity)(new UpdateProcessActivityRequest(
            activityId: $answer->existingActivityId,
            processId: $answer->processId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
