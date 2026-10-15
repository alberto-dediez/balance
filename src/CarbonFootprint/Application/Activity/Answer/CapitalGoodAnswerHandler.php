<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterCapitalGoodActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterCapitalGoodActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateCapitalGoodActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateCapitalGoodActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CapitalGoodAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class CapitalGoodAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterCapitalGoodActivityService $registerCapitalGoodActivity,
        private UpdateCapitalGoodActivityService $updateCapitalGoodActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CapitalGood;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof CapitalGoodAnswer);

        $response = ($this->registerCapitalGoodActivity)(new RegisterCapitalGoodActivityRequest(
            accountId: $accountId,
            capitalGoodId: $answer->capitalGoodId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            acquisitionYear: $answer->acquisitionYear,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof CapitalGoodAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateCapitalGoodActivity)(new UpdateCapitalGoodActivityRequest(
            activityId: $answer->existingActivityId,
            capitalGoodId: $answer->capitalGoodId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            acquisitionYear: $answer->acquisitionYear,
        ), $user);
    }
}
