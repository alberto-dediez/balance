<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class SurplusFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public ?FoodType $foodType = null,
        public ?SurplusDestination $destination = null,
        public ?ServiceCategoryId $serviceCategoryId = null,
        public ?CriticalPointId $criticalPointId = null,
        public ?SurplusCauseId $surplusCauseId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
