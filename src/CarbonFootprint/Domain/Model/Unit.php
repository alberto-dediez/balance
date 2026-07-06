<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

enum Unit: string
{
    case Kilogram  = 'kg';
    case Gram      = 'g';
    case Tonne     = 't';
    case Piece     = 'ud';
    case Liter     = 'l';
    case CubicMeter   = 'm3';
    case KWhPCS    = 'kWhPCS';
    case KWh       = 'kWh';
    case Kilometer    = 'km';
    case Pernoctacion = 'pernoctacion';

    public function magnitude(): Magnitude
    {
        return match ($this) {
            self::Kilogram     => Magnitude::Mass,
            self::Gram         => Magnitude::Mass,
            self::Tonne        => Magnitude::Mass,
            self::Piece        => Magnitude::Pieces,
            self::Liter        => Magnitude::Volume,
            self::CubicMeter   => Magnitude::Volume,
            self::KWhPCS       => Magnitude::Energy,
            self::KWh          => Magnitude::Energy,
            self::Kilometer    => Magnitude::Distance,
            self::Pernoctacion => Magnitude::Stay,
        };
    }

    public function factorToBase(): float
    {
        return match ($this) {
            self::Gram       => 0.001,
            self::Tonne      => 1000.0,
            self::CubicMeter => 1000.0,
            self::KWhPCS     => 1.0,
            default          => 1.0,
        };
    }
}
