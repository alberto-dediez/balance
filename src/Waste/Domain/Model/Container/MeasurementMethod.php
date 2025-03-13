<?php

namespace App\Waste\Domain\Model\Container;

enum MeasurementMethod: string
{
    case Volume = 'VOLUME';

    case Weight = 'WEIGHT';
}