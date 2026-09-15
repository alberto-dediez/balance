<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterRoadTransportActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateRoadTransportActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RoadTransportAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class RoadTransportAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterRoadTransportActivityService $registerRoadTransportActivity,
        private UpdateRoadTransportActivityService $updateRoadTransportActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RoadTransport;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof RoadTransportAnswer);

        $response = ($this->registerRoadTransportActivity)(new RegisterRoadTransportActivityRequest(
            accountId: $accountId,
            roadTransportId: $answer->roadTransportId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof RoadTransportAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateRoadTransportActivity)(new UpdateRoadTransportActivityRequest(
            activityId: $answer->existingActivityId,
            roadTransportId: $answer->roadTransportId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);
    }
}
