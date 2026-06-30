<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

enum EventCarbonFootprintStatus: string
{
    case Started = 'started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
