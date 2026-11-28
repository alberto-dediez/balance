<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

enum ContestHostType: string
{
    case Event = 'event';

    case Property = 'property';

    case TripCalculationLink = 'trip_link';
}
