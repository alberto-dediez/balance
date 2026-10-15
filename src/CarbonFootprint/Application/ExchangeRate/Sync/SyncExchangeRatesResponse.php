<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Sync;

use App\Shared\Application\CommandBus\CommandResponse;

class SyncExchangeRatesResponse implements CommandResponse
{
    /** @param list<string> $unknownCurrencies */
    public function __construct(
        public int $registered = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public array $unknownCurrencies = [],
        public ?\DateTimeImmutable $date = null,
        public bool $failed = false,
        public ?string $failure = null,
    ) {}
}
