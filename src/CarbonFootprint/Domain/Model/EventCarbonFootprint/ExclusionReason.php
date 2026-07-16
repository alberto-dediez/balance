<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

enum ExclusionReason: string
{
    case NotApplicable   = 'not_applicable';
    case NoDataAvailable = 'no_data_available';
    case NotSignificant  = 'not_significant';
    case OutOfScope      = 'out_of_scope';
    case Other           = 'other';
}
