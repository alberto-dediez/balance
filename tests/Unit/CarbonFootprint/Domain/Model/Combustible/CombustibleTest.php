<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class CombustibleTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $combustible->translations());
        $combustible->translate('translated-name', Locale::English);
        $this->assertCount(1, $combustible->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );
        $combustible->translate('translated-name-en', Locale::English);
        $combustible->translate('translated-name-it', Locale::Italian);
        $combustible->translate('translated-name-ge', Locale::German);

        $this->assertEquals('combustible-name', $combustible->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $combustible = new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
        );
        $combustible->translate('translated-name-en', Locale::English);
        $combustible->translate('translated-name-it', Locale::Italian);
        $combustible->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $combustible->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $combustible->emissionFactors());
        $this->assertSame('1.5', $combustible->emissionFactorFor(2024)?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $combustible = $this->combustible();

        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $combustible->emissionFactors());
        $this->assertSame('2.0', $combustible->emissionFactorFor(2024)?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $combustible = $this->combustible();

        $this->assertNull($combustible->emissionFactorFor(1999));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $combustible->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $combustible->emissionFactors());
        $this->assertNull($combustible->emissionFactorFor(2023));
        $this->assertSame('2.5', $combustible->emissionFactorFor(2024)?->co2eq());
        $this->assertSame('3.0', $combustible->emissionFactorFor(2025)?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $combustible = $this->combustible();

        $this->expectException(\InvalidArgumentException::class);

        $combustible->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingCombustibleUnitRestampsEmissionFactors(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $combustible->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $combustible->emissionFactorFor(2023)?->unit());

        $combustible->update('combustible-name', Unit::Piece);

        $factor2023 = $combustible->emissionFactorFor(2023);
        $factor2024 = $combustible->emissionFactorFor(2024);

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        // Other fields are preserved.
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $combustible = $this->combustible();
        $combustible->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $combustible->removeEmissionFactor(2024);

        $this->assertCount(0, $combustible->emissionFactors());
    }

    private function combustible(): Combustible
    {
        return new Combustible(
            id: CombustibleId::generate(),
            name: 'combustible-name',
            unit: Unit::Kilogram,
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
            unit: Unit::Kilogram,
            source: 'test-source',
        );
    }
}
