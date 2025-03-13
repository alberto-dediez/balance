<?php

namespace App\Waste\Domain\Model\Container;

enum Purpose: string
{
    case Waste = 'WASTE';

    case Recycling = 'RECYCLING';
}