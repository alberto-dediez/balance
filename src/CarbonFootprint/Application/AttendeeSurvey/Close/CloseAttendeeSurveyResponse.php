<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Close;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\Shared\Application\CommandBus\CommandResponse;

class CloseAttendeeSurveyResponse implements CommandResponse
{
    public function __construct(
        public AttendeeSurvey $attendeeSurvey,
        public bool $overnightStayCatalogMissing,
    ) {}
}
