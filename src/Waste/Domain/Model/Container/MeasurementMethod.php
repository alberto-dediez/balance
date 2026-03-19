<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Measurement\Unit;

enum MeasurementMethod: string
{
    case Volume = 'VOLUME';

    case Weight = 'WEIGHT';

    public function unit(): string
    {
        return match ($this) {
            self::Volume => Unit::Percentage->value,
            self::Weight => Unit::Kilogram->value,
        };
    }
}
