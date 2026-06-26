<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\Shared\Domain\Model\Filter;

class ElectricityMarketerFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
