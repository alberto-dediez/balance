<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

final readonly class ExchangeRateFeedEntry
{
    public function __construct(
        public string $currency,
        public string $rate,
    ) {}
}
