<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

enum MachineryType: string
{
    case Agricultural = 'agricultural';
    case Forestry = 'forestry';
    case CommercialInstitutionalIndustrial = 'commercial_institutional_industrial';
}
