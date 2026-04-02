<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Model\Filter;

class CriticalPointFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?string $accountId = null,
        public ?string $propertyId = null,
        public ?ServiceCategoryId $serviceCategoryId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
