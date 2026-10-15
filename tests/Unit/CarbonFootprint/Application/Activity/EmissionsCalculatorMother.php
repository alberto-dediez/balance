<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\UnitConverter;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;

class EmissionsCalculatorMother
{
    public static function create(?ExchangeRateRepository $exchangeRates = null): EmissionsCalculator
    {
        return new EmissionsCalculator(new UnitConverter($exchangeRates ?? ExchangeRateRepositoryMother::inMemory()));
    }
}
