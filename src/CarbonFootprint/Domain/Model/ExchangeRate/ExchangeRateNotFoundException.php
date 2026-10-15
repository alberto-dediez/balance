<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\Unit;

final class ExchangeRateNotFoundException extends \DomainException
{
    public function __construct(Unit $currency)
    {
        parent::__construct(sprintf('No exchange rate available for currency "%s".', $currency->value));
    }
}
