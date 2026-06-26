<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MobilityTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );

        $this->assertCount(0, $mobility->translations());
        $mobility->translate('translated-name', Locale::English);
        $this->assertCount(1, $mobility->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );
        $mobility->translate('translated-name-en', Locale::English);
        $mobility->translate('translated-name-it', Locale::Italian);
        $mobility->translate('translated-name-ge', Locale::German);

        $this->assertEquals('mobility-name', $mobility->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $mobility = new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
        );
        $mobility->translate('translated-name-en', Locale::English);
        $mobility->translate('translated-name-it', Locale::Italian);
        $mobility->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $mobility->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $mobility = $this->mobility();

        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $mobility->emissionFactors());
        $this->assertSame('1.5', $mobility->emissionFactorFor(2024)?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $mobility = $this->mobility();

        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $mobility->emissionFactors());
        $this->assertSame('2.0', $mobility->emissionFactorFor(2024)?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $mobility = $this->mobility();

        $this->assertNull($mobility->emissionFactorFor(1999));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $mobility->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $mobility->emissionFactors());
        $this->assertNull($mobility->emissionFactorFor(2023));
        $this->assertSame('2.5', $mobility->emissionFactorFor(2024)?->co2eq());
        $this->assertSame('3.0', $mobility->emissionFactorFor(2025)?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $mobility = $this->mobility();

        $this->expectException(\InvalidArgumentException::class);

        $mobility->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingMobilityUnitRestampsEmissionFactors(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $mobility->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilometer, $mobility->emissionFactorFor(2023)?->unit());

        $mobility->update('mobility-name', Unit::Piece);

        $factor2023 = $mobility->emissionFactorFor(2023);
        $factor2024 = $mobility->emissionFactorFor(2024);

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        // Other fields are preserved.
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $mobility = $this->mobility();
        $mobility->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $mobility->removeEmissionFactor(2024);

        $this->assertCount(0, $mobility->emissionFactors());
    }

    public function testDefaultUnitIsKilometer(): void
    {
        $mobility = new Mobility(id: MobilityId::generate());

        $this->assertSame(Unit::Kilometer, $mobility->unit());
    }

    private function mobility(): Mobility
    {
        return new Mobility(
            id: MobilityId::generate(),
            name: 'mobility-name',
            unit: Unit::Kilometer,
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
            unit: Unit::Kilometer,
            source: 'test-source',
        );
    }
}
