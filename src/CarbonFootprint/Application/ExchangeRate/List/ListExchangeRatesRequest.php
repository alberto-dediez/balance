<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate\List;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\Shared\Application\CommandBus\Command;

class ListExchangeRatesRequest extends ExchangeRateFilter implements Command {}
