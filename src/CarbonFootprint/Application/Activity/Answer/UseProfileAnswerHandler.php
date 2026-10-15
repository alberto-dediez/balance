<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterUseProfileActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterUseProfileActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateUseProfileActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateUseProfileActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\UseProfileAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class UseProfileAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterUseProfileActivityService $registerUseProfileActivity,
        private UpdateUseProfileActivityService $updateUseProfileActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::UseProfile;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof UseProfileAnswer);

        $response = ($this->registerUseProfileActivity)(new RegisterUseProfileActivityRequest(
            accountId: $accountId,
            useProfileId: $answer->useProfileId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof UseProfileAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateUseProfileActivity)(new UpdateUseProfileActivityRequest(
            activityId: $answer->existingActivityId,
            useProfileId: $answer->useProfileId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
