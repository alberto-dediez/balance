<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

interface ExchangeRateGateway
{
    /** @throws ExchangeRateSourceUnavailableException */
    public function fetch(): ExchangeRateFeed;
}
