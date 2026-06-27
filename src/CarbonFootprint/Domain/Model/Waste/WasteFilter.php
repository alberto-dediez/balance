<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Waste;

use App\Shared\Domain\Model\Filter;

class WasteFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
