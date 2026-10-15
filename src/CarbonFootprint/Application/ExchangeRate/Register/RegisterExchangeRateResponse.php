<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\Register;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterExchangeRateResponse implements CommandResponse
{
    public function __construct(public ExchangeRate $exchangeRate) {}
}
