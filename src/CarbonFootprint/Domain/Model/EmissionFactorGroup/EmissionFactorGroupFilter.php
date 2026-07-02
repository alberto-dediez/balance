<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\Shared\Domain\Model\Filter;

class EmissionFactorGroupFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
