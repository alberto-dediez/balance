<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class ElectricityMarketerTest extends TestCase
{
    public function testSetEmissionFactorAddsIt(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $electricityMarketer->emissionFactors());
        $this->assertSame('1.5', $electricityMarketer->emissionFactorFor(2024)?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $electricityMarketer->emissionFactors());
        $this->assertSame('2.0', $electricityMarketer->emissionFactorFor(2024)?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->assertNull($electricityMarketer->emissionFactorFor(1999));
    }

    public function testSetEmissionFactorUsesKWhUnit(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $electricityMarketer->emissionFactorFor(2024);

        $this->assertNotNull($factor);
        $this->assertSame(Unit::KWh, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $electricityMarketer->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $electricityMarketer->emissionFactors());
        $this->assertNull($electricityMarketer->emissionFactorFor(2023));
        $this->assertSame('2.5', $electricityMarketer->emissionFactorFor(2024)?->co2eq());
        $this->assertSame('3.0', $electricityMarketer->emissionFactorFor(2025)?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $electricityMarketer = $this->electricityMarketer();

        $this->expectException(\InvalidArgumentException::class);

        $electricityMarketer->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $electricityMarketer = $this->electricityMarketer();
        $electricityMarketer->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $electricityMarketer->removeEmissionFactor(2024);

        $this->assertCount(0, $electricityMarketer->emissionFactors());
    }

    private function electricityMarketer(): ElectricityMarketer
    {
        return new ElectricityMarketer(
            id: ElectricityMarketerId::generate(),
            name: 'electricity-marketer-name',
        );
    }

    private function emissionFactor(int $year, string $co2eq): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: '0.1',
            ch4: '0.2',
            n2o: '0.3',
            year: $year,
            unit: Unit::KWh,
            source: 'test-source',
        );
    }
}
