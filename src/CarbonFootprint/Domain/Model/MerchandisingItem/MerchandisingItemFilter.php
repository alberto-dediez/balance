<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\Shared\Domain\Model\Filter;

class MerchandisingItemFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?MerchandisingItemCategory $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
