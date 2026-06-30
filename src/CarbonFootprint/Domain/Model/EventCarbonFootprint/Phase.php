<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

enum Phase: string
{
    case Materials   = 'materials';
    case Setup       = 'setup';
    case Celebration = 'celebration';
    case Teardown    = 'teardown';
}
