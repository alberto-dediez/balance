<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\Shared\Domain\Model\Filter;

class PurchaseServiceFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?PurchaseServiceCategory $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
