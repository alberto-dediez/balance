<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

enum ItemCondition: string
{
    case New = 'new';
    case Recycled = 'recycled';
}
