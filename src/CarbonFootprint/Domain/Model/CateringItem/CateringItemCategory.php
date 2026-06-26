<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

enum CateringItemCategory: string
{
    case Breakfast = 'breakfast';
    case Snack     = 'snack';
    case Drink     = 'drink';
    case Menu      = 'menu';
    case General   = 'general';
    case Others    = 'others';
}
