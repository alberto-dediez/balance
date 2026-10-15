<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepositoryMother;
use PHPUnit\Framework\TestCase;

class EmissionsCalculatorTest extends TestCase
{
    public function testCalculatesUsingTheQuantityUnitDirectlyWhenItMatchesTheFactor(): void
    {
        $calculator = EmissionsCalculatorMother::create();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $emissions = $calculator->calculate($factor, new Quantity(10.0, Unit::Kilogram));

        $this->assertSame('15', $emissions->co2eq());
    }

    public function testConvertsTheQuantityToTheFactorUnitBeforeMultiplying(): void
    {
        $calculator = EmissionsCalculatorMother::create();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $emissions = $calculator->calculate($factor, new Quantity(2.0, Unit::Tonne));

        $this->assertSame('3000', $emissions->co2eq());
    }

    public function testThrowsWhenQuantityMagnitudeIsIncompatibleWithTheFactorUnit(): void
    {
        $calculator = EmissionsCalculatorMother::create();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $this->expectException(IncompatibleUnitsException::class);
        $calculator->calculate($factor, new Quantity(10.0, Unit::Liter));
    }

    public function testConvertsAForeignCurrencyQuantityToAnEuroFactor(): void
    {
        $exchangeRates = ExchangeRateRepositoryMother::inMemory();
        $exchangeRates->add(ExchangeRateMother::registered(currency: Unit::UsDollar, rate: '1.25'));
        $calculator = EmissionsCalculatorMother::create($exchangeRates);
        $factor = new EmissionFactor('0.5', '0.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Euro);

        $emissions = $calculator->calculate($factor, new Quantity(250.0, Unit::UsDollar));

        $this->assertEqualsWithDelta(100.0, (float) $emissions->co2eq(), 1e-9);
    }

    public function testThrowsWhenTheExchangeRateIsMissing(): void
    {
        $calculator = EmissionsCalculatorMother::create();
        $factor = new EmissionFactor('0.5', '0.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Euro);

        $this->expectException(ExchangeRateNotFoundException::class);
        $calculator->calculate($factor, new Quantity(250.0, Unit::UsDollar));
    }
}
