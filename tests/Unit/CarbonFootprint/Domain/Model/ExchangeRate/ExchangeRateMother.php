<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\Unit;

class ExchangeRateMother
{
    public static function registered(
        Unit $currency = Unit::UsDollar,
        string $date = '2026-10-14',
        string $rate = '1.25',
        ?string $id = null,
    ): ExchangeRate {
        $exchangeRate = new ExchangeRate(
            id: new ExchangeRateId($id ?? (string) ExchangeRateId::generate()),
        );

        $exchangeRate->register($currency, new \DateTimeImmutable($date), $rate, new \DateTimeImmutable('NOW'));

        return $exchangeRate;
    }
}
