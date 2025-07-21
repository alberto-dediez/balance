<?php

namespace App\Waste\Domain\Model\Container;

enum MeasurementMethod: string
{
    case Volume = 'VOLUME';

    case Weight = 'WEIGHT';

    public function unit(): string
    {
        return match ($this) {
            self::Volume => '%',
            self::Weight => 'Kg',
        };
    }
}