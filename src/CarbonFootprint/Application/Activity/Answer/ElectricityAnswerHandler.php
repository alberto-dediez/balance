<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterElectricityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateElectricityActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class ElectricityAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterElectricityActivityService $registerElectricityActivity,
        private UpdateElectricityActivityService $updateElectricityActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof ElectricityAnswer);

        $response = ($this->registerElectricityActivity)(new RegisterElectricityActivityRequest(
            accountId: $accountId,
            electricityMarketerId: $answer->electricityMarketerId,
            gdOFactorId: $answer->gdOFactorId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            countryId: $countryId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof ElectricityAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateElectricityActivity)(new UpdateElectricityActivityRequest(
            activityId: $answer->existingActivityId,
            electricityMarketerId: $answer->electricityMarketerId,
            gdOFactorId: $answer->gdOFactorId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            countryId: $countryId,
        ), $user);
    }
}
