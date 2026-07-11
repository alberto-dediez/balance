<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;

final class RegisterAttendeeSurveyResponseResponse
{
    public function __construct(
        public readonly AttendeeSurveyResponseId $attendeeSurveyResponseId,
    ) {}
}
