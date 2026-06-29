<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\Shared\Domain\Model\Filter;

class GdOFactorFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
