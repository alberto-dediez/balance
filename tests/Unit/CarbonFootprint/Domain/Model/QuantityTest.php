<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model;

use App\CarbonFootprint\Domain\Model\ExchangeRateRequiredException;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{
    public function testConvertToSameUnitReturnsSameInstance(): void
    {
        $quantity = new Quantity(10.0, Unit::Kilogram);

        $this->assertSame($quantity, $quantity->convertTo(Unit::Kilogram));
    }

    public function testConvertsTonneToKilogram(): void
    {
        $quantity = new Quantity(2.0, Unit::Tonne);

        $converted = $quantity->convertTo(Unit::Kilogram);

        $this->assertSame(2000.0, $converted->value());
        $this->assertSame(Unit::Kilogram, $converted->unit());
    }

    public function testConvertsKilogramToTonne(): void
    {
        $quantity = new Quantity(2000.0, Unit::Kilogram);

        $converted = $quantity->convertTo(Unit::Tonne);

        $this->assertSame(2.0, $converted->value());
        $this->assertSame(Unit::Tonne, $converted->unit());
    }

    public function testConvertsGramToTonne(): void
    {
        $quantity = new Quantity(3_000_000.0, Unit::Gram);

        $converted = $quantity->convertTo(Unit::Tonne);

        $this->assertSame(3.0, $converted->value());
        $this->assertSame(Unit::Tonne, $converted->unit());
    }

    public function testConvertsTonneToGram(): void
    {
        $quantity = new Quantity(3.0, Unit::Tonne);

        $converted = $quantity->convertTo(Unit::Gram);

        $this->assertSame(3_000_000.0, $converted->value());
        $this->assertSame(Unit::Gram, $converted->unit());
    }

    public function testConvertsCubicMeterToLiter(): void
    {
        $quantity = new Quantity(1.5, Unit::CubicMeter);

        $converted = $quantity->convertTo(Unit::Liter);

        $this->assertSame(1500.0, $converted->value());
        $this->assertSame(Unit::Liter, $converted->unit());
    }

    public function testConvertsKWhPCSToKWhWithFactorOne(): void
    {
        $quantity = new Quantity(10.0, Unit::KWhPCS);

        $converted = $quantity->convertTo(Unit::KWh);

        $this->assertSame(10.0, $converted->value());
        $this->assertSame(Unit::KWh, $converted->unit());
    }

    public function testThrowsWhenConvertingBetweenIncompatibleMagnitudes(): void
    {
        $quantity = new Quantity(10.0, Unit::Kilogram);

        $this->expectException(IncompatibleUnitsException::class);
        $quantity->convertTo(Unit::Liter);
    }

    public function testThrowsWhenConvertingBetweenCurrencies(): void
    {
        $quantity = new Quantity(10.0, Unit::UsDollar);

        $this->expectException(ExchangeRateRequiredException::class);
        $quantity->convertTo(Unit::Euro);
    }

    public function testThrowsWhenConvertingCurrencyToMass(): void
    {
        $quantity = new Quantity(10.0, Unit::UsDollar);

        $this->expectException(IncompatibleUnitsException::class);
        $quantity->convertTo(Unit::Kilogram);
    }

    public function testThrowsWhenConvertingTonneKilometreToKilogram(): void
    {
        $quantity = new Quantity(10.0, Unit::TonneKilometre);

        $this->expectException(IncompatibleUnitsException::class);
        $quantity->convertTo(Unit::Kilogram);
    }

    public function testThrowsWhenConvertingKilometreToTonneKilometre(): void
    {
        $quantity = new Quantity(10.0, Unit::Kilometer);

        $this->expectException(IncompatibleUnitsException::class);
        $quantity->convertTo(Unit::TonneKilometre);
    }
}
