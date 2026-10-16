<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model;

use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testCurrenciesHaveCurrencyMagnitude(): void
    {
        foreach (['EUR', 'USD', 'JPY', 'CZK', 'DKK', 'GBP', 'HUF', 'PLN', 'RON', 'SEK', 'CHF', 'ISK', 'NOK', 'TRY', 'AUD', 'BRL', 'CAD', 'CNY', 'HKD', 'IDR', 'ILS', 'INR', 'KRW', 'MXN', 'MYR', 'NZD', 'PHP', 'SGD', 'THB', 'ZAR'] as $code) {
            $unit = Unit::from($code);

            $this->assertSame(Magnitude::Currency, $unit->magnitude());
            $this->assertTrue($unit->isCurrency());
            $this->assertNull($unit->fixedFactorToBase());
        }
    }

    public function testPhysicalUnitsKeepTheirFixedFactors(): void
    {
        $this->assertSame(0.001, Unit::Gram->fixedFactorToBase());
        $this->assertSame(1000.0, Unit::Tonne->fixedFactorToBase());
        $this->assertSame(1000.0, Unit::CubicMeter->fixedFactorToBase());
        $this->assertSame(1.0, Unit::KWhPCS->fixedFactorToBase());
        $this->assertSame(1.0, Unit::Kilogram->fixedFactorToBase());
        $this->assertFalse(Unit::Kilogram->isCurrency());
    }

    public function testCurrenciesListsThirtyCurrencyUnits(): void
    {
        $currencies = Unit::currencies();

        $this->assertCount(30, $currencies);
        foreach ($currencies as $currency) {
            $this->assertTrue($currency->isCurrency());
        }
    }

    public function testEuroIsTheCurrencyBaseUnit(): void
    {
        $this->assertSame(Unit::Euro, Magnitude::Currency->baseUnit());
    }

    public function testSquareMeterBelongsToAreaMagnitude(): void
    {
        $this->assertSame(Magnitude::Area, Unit::SquareMeter->magnitude());
        $this->assertFalse(Unit::SquareMeter->isCurrency());
        $this->assertSame(1.0, Unit::SquareMeter->fixedFactorToBase());
        $this->assertSame(Unit::SquareMeter, Magnitude::Area->baseUnit());
    }

    public function testSquareMeterCannotConvertToOtherMagnitudes(): void
    {
        $this->expectException(IncompatibleUnitsException::class);

        (new Quantity(10, Unit::SquareMeter))->convertTo(Unit::Kilogram);
    }

    public function testTonneKilometreBelongsToFreightMagnitude(): void
    {
        $this->assertSame(Magnitude::Freight, Unit::TonneKilometre->magnitude());
        $this->assertSame(Unit::TonneKilometre, Magnitude::Freight->baseUnit());
        $this->assertSame(1.0, Unit::TonneKilometre->fixedFactorToBase());
        $this->assertFalse(Unit::TonneKilometre->isCurrency());
    }
}
