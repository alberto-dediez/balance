<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

enum LegKind: string
{
    case Intercity = 'intercity';
    case City = 'city';
}
