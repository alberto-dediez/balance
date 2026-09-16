<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterRefrigerantGasActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateRefrigerantGasActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class RefrigerantGasAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterRefrigerantGasActivityService $registerRefrigerantGasActivity,
        private UpdateRefrigerantGasActivityService $updateRefrigerantGasActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RefrigerantGas;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof RefrigerantGasAnswer);

        $response = ($this->registerRefrigerantGasActivity)(new RegisterRefrigerantGasActivityRequest(
            accountId: $accountId,
            refrigerantGasId: $answer->refrigerantGasId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof RefrigerantGasAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateRefrigerantGasActivity)(new UpdateRefrigerantGasActivityRequest(
            activityId: $answer->existingActivityId,
            refrigerantGasId: $answer->refrigerantGasId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);
    }
}
