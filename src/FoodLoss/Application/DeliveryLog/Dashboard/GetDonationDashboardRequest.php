<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;

class GetDonationDashboardRequest extends DeliveryLogFilter
{
    public ?float $kgPerRation = DeliveryLogUnit::MITECO_KG_PER_RATION;

    public ?DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg;
}
