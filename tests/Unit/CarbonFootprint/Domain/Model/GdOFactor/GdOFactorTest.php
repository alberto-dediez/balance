<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class GdOFactorTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->assertCount(0, $gdOFactor->translations());
        $gdOFactor->translate('translated-name', Locale::English);
        $this->assertCount(1, $gdOFactor->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->translate('translated-name-en', Locale::English);
        $gdOFactor->translate('translated-name-it', Locale::Italian);
        $gdOFactor->translate('translated-name-ge', Locale::German);

        $this->assertEquals('gdo-factor-name', $gdOFactor->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->translate('translated-name-en', Locale::English);
        $gdOFactor->translate('translated-name-it', Locale::Italian);
        $gdOFactor->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $gdOFactor->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $gdOFactor->emissionFactors());
        $this->assertSame('1.5', $gdOFactor->emissionFactorFor(2024)?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $gdOFactor->emissionFactors());
        $this->assertSame('2.0', $gdOFactor->emissionFactorFor(2024)?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->assertNull($gdOFactor->emissionFactorFor(1999));
    }

    public function testSetEmissionFactorUsesKWhUnit(): void
    {
        $gdOFactor = $this->gdOFactor();

        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $gdOFactor->emissionFactorFor(2024);

        $this->assertNotNull($factor);
        $this->assertSame(Unit::KWh, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $gdOFactor->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $gdOFactor->emissionFactors());
        $this->assertNull($gdOFactor->emissionFactorFor(2023));
        $this->assertSame('2.5', $gdOFactor->emissionFactorFor(2024)?->co2eq());
        $this->assertSame('3.0', $gdOFactor->emissionFactorFor(2025)?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $gdOFactor = $this->gdOFactor();

        $this->expectException(\InvalidArgumentException::class);

        $gdOFactor->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $gdOFactor = $this->gdOFactor();
        $gdOFactor->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $gdOFactor->removeEmissionFactor(2024);

        $this->assertCount(0, $gdOFactor->emissionFactors());
    }

    private function gdOFactor(): GdOFactor
    {
        return new GdOFactor(
            id: GdOFactorId::generate(),
            name: 'gdo-factor-name',
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
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: Unit::KWh,
            source: 'test-source',
        );
    }
}
