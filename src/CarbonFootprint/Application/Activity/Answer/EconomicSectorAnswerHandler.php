<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterEconomicSectorActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateEconomicSectorActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class EconomicSectorAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterEconomicSectorActivityService $registerEconomicSectorActivity,
        private UpdateEconomicSectorActivityService $updateEconomicSectorActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::EconomicSector;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof EconomicSectorAnswer);

        $response = ($this->registerEconomicSectorActivity)(new RegisterEconomicSectorActivityRequest(
            accountId: $accountId,
            economicSectorId: $answer->economicSectorId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof EconomicSectorAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateEconomicSectorActivity)(new UpdateEconomicSectorActivityRequest(
            activityId: $answer->existingActivityId,
            economicSectorId: $answer->economicSectorId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
