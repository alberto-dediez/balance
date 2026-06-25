<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

enum Unit: string
{
    case Kilogram = 'kg';
    case Piece = 'ud';
}
