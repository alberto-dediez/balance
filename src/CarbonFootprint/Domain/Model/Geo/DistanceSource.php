<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

enum DistanceSource: string
{
    case Route = 'route';
    case GreatCircle = 'great_circle';
    case Manual = 'manual';
}
