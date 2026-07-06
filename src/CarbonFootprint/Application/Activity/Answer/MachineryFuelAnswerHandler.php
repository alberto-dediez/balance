<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMachineryFuelActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateMachineryFuelActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MachineryFuelAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\User\User;

final class MachineryFuelAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterMachineryFuelActivityService $registerMachineryFuelActivity,
        private UpdateMachineryFuelActivityService $updateMachineryFuelActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MachineryFuel;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        assert($answer instanceof MachineryFuelAnswer);

        $response = ($this->registerMachineryFuelActivity)(new RegisterMachineryFuelActivityRequest(
            machineryFuelId: $answer->machineryFuelId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof MachineryFuelAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateMachineryFuelActivity)(new UpdateMachineryFuelActivityRequest(
            activityId: $answer->existingActivityId,
            machineryFuelId: $answer->machineryFuelId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
            source: $answer->source,
        ), $user);
    }
}
