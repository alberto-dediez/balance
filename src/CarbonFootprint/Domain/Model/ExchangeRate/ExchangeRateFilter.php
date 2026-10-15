<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Filter;

class ExchangeRateFilter
{
    use Filter;

    public function __construct(
        public ?Unit $currency = null,
        int $limit = 50,
    ) {
        $this->limit = $limit;
    }
}
