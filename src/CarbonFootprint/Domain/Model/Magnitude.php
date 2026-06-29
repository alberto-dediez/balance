<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

enum Magnitude: string
{
    case Mass     = 'mass';
    case Volume   = 'volume';
    case Pieces   = 'pieces';
    case Energy   = 'energy';
    case Distance = 'distance';
    case Stay     = 'stay';
}
