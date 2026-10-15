<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Register;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterExchangeRateRequest implements Command
{
    public function __construct(
        public Unit $currency,
        public \DateTimeImmutable $date,
        public string $rate,
        public \DateTimeImmutable $fetchedAt = new \DateTimeImmutable('NOW'),
        public ?ExchangeRateId $id = null,
    ) {}
}
