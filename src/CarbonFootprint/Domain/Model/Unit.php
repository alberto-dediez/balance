<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

enum Unit: string
{
    case Kilogram  = 'kg';
    case Piece     = 'ud';
    case Liter     = 'l';
    case KWhPCS    = 'kWhPCS';
    case KWh       = 'kWh';
    case Kilometer = 'km';

    public function magnitude(): Magnitude
    {
        return match ($this) {
            self::Kilogram  => Magnitude::Mass,
            self::Piece     => Magnitude::Pieces,
            self::Liter     => Magnitude::Volume,
            self::KWhPCS    => Magnitude::Energy,
            self::KWh       => Magnitude::Energy,
            self::Kilometer => Magnitude::Distance,
        };
    }
}
