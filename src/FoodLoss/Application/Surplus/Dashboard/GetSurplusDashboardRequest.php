<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\Surplus\SurplusFilter;

class GetSurplusDashboardRequest extends SurplusFilter
{
    public const MITECO_KG_PER_RATION = 0.4;

    public ?float $kgPerRation = self::MITECO_KG_PER_RATION;

    public ?DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg;
}
