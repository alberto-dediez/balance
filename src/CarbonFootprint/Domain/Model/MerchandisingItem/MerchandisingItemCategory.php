<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

enum MerchandisingItemCategory: string
{
    case Merchandising = 'merchandising';
    case Clothing      = 'clothing';
    case Sport         = 'sport';
    case Others        = 'others';
}
