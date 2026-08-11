<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\CarbonFootprint\Domain\Model\Emissions;

final class RegisterAttendeeSurveyResponseResponse
{
    public function __construct(
        public readonly AttendeeSurveyResponseId $attendeeSurveyResponseId,
        public readonly Emissions $emissions = new Emissions(),
        public readonly Emissions $mobilityEmissions = new Emissions(),
        public readonly Emissions $overnightEmissions = new Emissions(),
        public readonly bool $overnightStayCatalogMissing = false,
    ) {}
}
