<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Property\Domain\Model\Property\PropertyId;

class PackagingPropertyStockData
{
    public function __construct(
        public PropertyId $propertyId,
        public int $stock = 0,
    ) {}
}
