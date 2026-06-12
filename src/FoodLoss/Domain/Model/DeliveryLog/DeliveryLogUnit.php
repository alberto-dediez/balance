<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

enum DeliveryLogUnit: string
{
    case Kg = 'kg';
    case Rations = 'rations';

    public const MITECO_KG_PER_RATION = 0.4;
}
