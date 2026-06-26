<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\Shared\Domain\Model\Filter;

class CateringItemFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?CateringItemCategory $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
