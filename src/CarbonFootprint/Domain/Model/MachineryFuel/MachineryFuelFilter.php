<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\Shared\Domain\Model\Filter;

class MachineryFuelFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?MachineryType $machineryType = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
