<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\Shared\Domain\Model\Filter;

class CapitalGoodFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?CapitalGoodCategory $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
