<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UnitConverter;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryExchangeRateRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    private InMemoryExchangeRateRepository $exchangeRates;
    private UnitConverter $converter;

    protected function setUp(): void
    {
        $this->exchangeRates = ExchangeRateRepositoryMother::inMemory();
        $this->converter = new UnitConverter($this->exchangeRates);
    }

    public function testSameUnitReturnsTheSameInstance(): void
    {
        $quantity = new Quantity(10.0, Unit::UsDollar);

        $this->assertSame($quantity, $this->converter->convert($quantity, Unit::UsDollar));
    }

    public function testPhysicalUnitsAreConvertedWithFixedFactors(): void
    {
        $converted = $this->converter->convert(new Quantity(2.0, Unit::Tonne), Unit::Kilogram);

        $this->assertEqualsWithDelta(2000.0, $converted->value(), 1e-9);
        $this->assertSame(Unit::Kilogram, $converted->unit());
    }

    public function testConvertsForeignCurrencyToEuro(): void
    {
        $this->exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));

        $converted = $this->converter->convert(new Quantity(125.0, Unit::UsDollar), Unit::Euro);

        $this->assertEqualsWithDelta(100.0, $converted->value(), 1e-9);
        $this->assertSame(Unit::Euro, $converted->unit());
    }

    public function testConvertsEuroToForeignCurrency(): void
    {
        $this->exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));

        $converted = $this->converter->convert(new Quantity(100.0, Unit::Euro), Unit::UsDollar);

        $this->assertEqualsWithDelta(125.0, $converted->value(), 1e-9);
        $this->assertSame(Unit::UsDollar, $converted->unit());
    }

    public function testConvertsBetweenTwoForeignCurrencies(): void
    {
        $this->exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));
        $this->exchangeRates->add(ExchangeRateMother::registered(currency: Unit::PoundSterling, rate: '0.80'));

        $converted = $this->converter->convert(new Quantity(125.0, Unit::UsDollar), Unit::PoundSterling);

        $this->assertEqualsWithDelta(80.0, $converted->value(), 1e-9);
        $this->assertSame(Unit::PoundSterling, $converted->unit());
    }

    public function testThrowsWhenTheExchangeRateIsMissing(): void
    {
        $this->expectException(ExchangeRateNotFoundException::class);
        $this->converter->convert(new Quantity(125.0, Unit::UsDollar), Unit::Euro);
    }

    public function testThrowsWhenConvertingMassToCurrency(): void
    {
        $this->expectException(IncompatibleUnitsException::class);
        $this->converter->convert(new Quantity(10.0, Unit::Kilogram), Unit::Euro);
    }
}
