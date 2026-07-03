<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\Shared\Domain\Model\Filter;

class OvernightStayFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?CategoryOvernight $category = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
