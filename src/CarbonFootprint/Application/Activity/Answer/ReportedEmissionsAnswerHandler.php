<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterReportedEmissionsActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateReportedEmissionsActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

final class ReportedEmissionsAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterReportedEmissionsActivityService $registerReportedEmissionsActivity,
        private UpdateReportedEmissionsActivityService $updateReportedEmissionsActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::ReportedEmissions;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, AccountId $accountId, User $user, ?CountryId $countryId = null): ActivityId
    {
        assert($answer instanceof ReportedEmissionsAnswer);

        $response = ($this->registerReportedEmissionsActivity)(new RegisterReportedEmissionsActivityRequest(
            accountId: $accountId,
            description: $answer->description,
            quantity: $this->toKilograms($answer->quantity),
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user, ?CountryId $countryId = null): void
    {
        assert($answer instanceof ReportedEmissionsAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateReportedEmissionsActivity)(new UpdateReportedEmissionsActivityRequest(
            activityId: $answer->existingActivityId,
            description: $answer->description,
            quantity: $this->toKilograms($answer->quantity),
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }

    private function toKilograms(Quantity $quantity): Quantity
    {
        if (!in_array($quantity->unit(), ReportedEmissionsActivity::allowedUnits(), true)) {
            throw new IncompatibleUnitsException($quantity->unit(), Unit::Kilogram);
        }

        return $quantity->convertTo(Unit::Kilogram);
    }
}
