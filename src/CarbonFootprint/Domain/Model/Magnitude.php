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
    case Currency = 'currency';

    public function baseUnit(): Unit
    {
        return match ($this) {
            self::Mass     => Unit::Kilogram,
            self::Volume   => Unit::Liter,
            self::Pieces   => Unit::Piece,
            self::Energy   => Unit::KWh,
            self::Distance => Unit::Kilometer,
            self::Stay     => Unit::Pernoctacion,
            self::Currency => Unit::Euro,
        };
    }
}
