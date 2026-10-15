<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Sync;

use App\Shared\Application\CommandBus\Command;

class SyncExchangeRatesRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
