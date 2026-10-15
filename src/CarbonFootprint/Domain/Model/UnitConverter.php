<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;

final class UnitConverter
{
    public function __construct(private readonly ExchangeRateRepository $exchangeRates) {}

    public function convert(Quantity $quantity, Unit $to): Quantity
    {
        if ($quantity->unit() === $to) {
            return $quantity;
        }

        if ($quantity->magnitude() !== $to->magnitude()) {
            throw new IncompatibleUnitsException($quantity->unit(), $to);
        }

        if ($quantity->unit()->isCurrency()) {
            return new Quantity($quantity->value() * $this->rate($to) / $this->rate($quantity->unit()), $to);
        }

        return $quantity->convertTo($to);
    }

    private function rate(Unit $currency): float
    {
        if (Unit::Euro === $currency) {
            return 1.0;
        }

        return (float) ($this->exchangeRates->findByCurrency($currency)?->rate() ?? throw new ExchangeRateNotFoundException($currency));
    }
}
