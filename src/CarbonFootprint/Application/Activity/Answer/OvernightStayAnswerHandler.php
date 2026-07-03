<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityRequest;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\OvernightStayAnswer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\User\User;

final class OvernightStayAnswerHandler implements ActivityAnswerHandler
{
    public function __construct(
        private RegisterOvernightStayActivityService $registerOvernightStayActivity,
        private UpdateOvernightStayActivityService $updateOvernightStayActivity,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::OvernightStay;
    }

    public function register(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): ActivityId
    {
        assert($answer instanceof OvernightStayAnswer);

        $response = ($this->registerOvernightStayActivity)(new RegisterOvernightStayActivityRequest(
            overnightStayId: $answer->overnightStayId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);

        return $response->activityId;
    }

    public function update(ActivityAnswer $answer, int $year, EmissionFactorGroupId $emissionFactorGroupId, User $user): void
    {
        assert($answer instanceof OvernightStayAnswer);
        assert(null !== $answer->existingActivityId);

        ($this->updateOvernightStayActivity)(new UpdateOvernightStayActivityRequest(
            activityId: $answer->existingActivityId,
            overnightStayId: $answer->overnightStayId,
            quantity: $answer->quantity,
            year: $year,
            emissionFactorGroupId: $emissionFactorGroupId,
        ), $user);
    }
}
