<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

enum Purpose: string
{
    case Waste = 'WASTE';

    case Recycling = 'RECYCLING';

    case Reuse = 'REUSE';
}
