<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterWasteActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateWasteActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\WasteAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class WasteAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterWasteActivityService $registerWasteActivity,
        private UpdateWasteActivityService $updateWasteActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Waste;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof WasteAnswer);

        $response = ($this->registerWasteActivity)(new RegisterWasteActivityRequest(
            accountId: $accountId,
            wasteId: $answer->wasteId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            containerCapacity: $answer->containerCapacity,
            fillPercentage: $answer->fillPercentage,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof WasteAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateWasteActivity)(new UpdateWasteActivityRequest(
            activityId: $answer->existingActivityId,
            wasteId: $answer->wasteId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
            containerCapacity: $answer->containerCapacity,
            fillPercentage: $answer->fillPercentage,
        ), $user);
    }
}
