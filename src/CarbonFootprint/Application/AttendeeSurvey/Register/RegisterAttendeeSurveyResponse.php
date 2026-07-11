<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterAttendeeSurveyResponse implements CommandResponse
{
    public function __construct(
        public AttendeeSurvey $attendeeSurvey,
    ) {}
}
