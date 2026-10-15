<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\List;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\Shared\Application\CommandBus\CommandResponse;

class ListExchangeRatesResponse implements CommandResponse
{
    /** @param list<ExchangeRate> $exchangeRates */
    public function __construct(
        public array $exchangeRates,
        public int $totalResults,
        public bool $canSync,
    ) {}
}
