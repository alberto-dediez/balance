<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

enum SupplyChainStage: string
{
    case Receiving = 'Receiving';

    case Storage = 'Storage';

    case Preparation = 'Preparation';

    case Service = 'Service';

    case PlateWaste = 'PlateWaste';
}
