<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class CriticalPointFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        public ?ServiceCategoryId $serviceCategoryId = null,
        public ?ActionId $actionId = null,
        public ?SurplusCauseId $surplusCauseId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
