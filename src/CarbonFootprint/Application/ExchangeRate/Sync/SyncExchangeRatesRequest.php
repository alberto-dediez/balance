<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Sync;

use App\Shared\Application\CommandBus\TimedCommand;

class SyncExchangeRatesRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
