<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterPurchaseServiceActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdatePurchaseServiceActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class PurchaseServiceAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterPurchaseServiceActivityService $registerPurchaseServiceActivity,
        private UpdatePurchaseServiceActivityService $updatePurchaseServiceActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::PurchaseService;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof PurchaseServiceAnswer);

        $response = ($this->registerPurchaseServiceActivity)(new RegisterPurchaseServiceActivityRequest(
            accountId: $accountId,
            purchaseServiceId: $answer->purchaseServiceId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof PurchaseServiceAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updatePurchaseServiceActivity)(new UpdatePurchaseServiceActivityRequest(
            activityId: $answer->existingActivityId,
            purchaseServiceId: $answer->purchaseServiceId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
