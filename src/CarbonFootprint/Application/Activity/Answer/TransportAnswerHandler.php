<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterTransportActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateTransportActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class TransportAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterTransportActivityService $registerTransportActivity,
        private UpdateTransportActivityService $updateTransportActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Transport;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof TransportAnswer);

        $response = ($this->registerTransportActivity)(new RegisterTransportActivityRequest(
            accountId: $accountId,
            transportId: $answer->transportId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            weight: $answer->weight,
            distanceKm: $answer->distanceKm,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof TransportAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateTransportActivity)(new UpdateTransportActivityRequest(
            activityId: $answer->existingActivityId,
            transportId: $answer->transportId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            weight: $answer->weight,
            distanceKm: $answer->distanceKm,
        ), $user);
    }
}
