<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MobilityAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class MobilityAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterMobilityActivityService $registerMobilityActivity,
        private UpdateMobilityActivityService $updateMobilityActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Mobility;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof MobilityAnswer);

        $response = ($this->registerMobilityActivity)(new RegisterMobilityActivityRequest(
            accountId: $accountId,
            mobilityId: $answer->mobilityId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof MobilityAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateMobilityActivity)(new UpdateMobilityActivityRequest(
            activityId: $answer->existingActivityId,
            mobilityId: $answer->mobilityId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
