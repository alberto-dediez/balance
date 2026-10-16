<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Filter;

class TransportFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?Unit $unit = null,
        public ?TransportMode $mode = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
