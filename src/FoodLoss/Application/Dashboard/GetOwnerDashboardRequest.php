<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class GetOwnerDashboardRequest
{
    use Filter;

    public ?float $kgPerRation = DeliveryLogUnit::MITECO_KG_PER_RATION;

    public ?DeliveryLogUnit $displayUnit = DeliveryLogUnit::Kg;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        int $limit = 0,
    ) {
        $this->limit = $limit;
    }
}
