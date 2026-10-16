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
    case Area     = 'area';
    case Currency = 'currency';
    case Freight = 'freight';

    public function baseUnit(): Unit
    {
        return match ($this) {
            self::Mass     => Unit::Kilogram,
            self::Volume   => Unit::Liter,
            self::Pieces   => Unit::Piece,
            self::Energy   => Unit::KWh,
            self::Distance => Unit::Kilometer,
            self::Stay     => Unit::Pernoctacion,
            self::Area     => Unit::SquareMeter,
            self::Currency => Unit::Euro,
            self::Freight => Unit::TonneKilometre,
        };
    }
}
