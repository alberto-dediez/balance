<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MaterialTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $material->translations());
        $material->translate('translated-name', Locale::English);
        $this->assertCount(1, $material->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );
        $material->translate('translated-name-en', Locale::English);
        $material->translate('translated-name-it', Locale::Italian);
        $material->translate('translated-name-ge', Locale::German);

        $this->assertEquals('material-name', $material->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $material = new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );
        $material->translate('translated-name-en', Locale::English);
        $material->translate('translated-name-it', Locale::Italian);
        $material->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $material->name());
    }

    public function testRegisterStoresUnitWeight(): void
    {
        $material = new Material(id: MaterialId::generate());

        $material->register('lanyard', MaterialCategory::Metal, Unit::Piece, '0.05');

        $this->assertSame('0.05', $material->unitWeight());
        $this->assertSame(Unit::Piece, $material->unit());
    }

    public function testUpdateChangesUnitWeight(): void
    {
        $material = $this->material();

        $material->update('material-name', MaterialCategory::Metal, Unit::Piece, '0.12');

        $this->assertSame('0.12', $material->unitWeight());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $material = $this->material();

        $material->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $material->emissionFactors());
        $this->assertSame('1.5', $material->emissionFactorFor(2024)?->newFactor()->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $material = $this->material();

        $material->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));
        $material->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $material->emissionFactors());
        $this->assertSame('2.0', $material->emissionFactorFor(2024)?->newFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $material = $this->material();

        $this->assertNull($material->emissionFactorFor(1999));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $material = $this->material();
        $material->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $material->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $material->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '2.5'), 'recycled' => $this->emissionFactor(2024, '2.5')],
            ['new' => $this->emissionFactor(2025, '3.0'), 'recycled' => $this->emissionFactor(2025, '3.0')],
        ]);

        $this->assertCount(2, $material->emissionFactors());
        $this->assertNull($material->emissionFactorFor(2023));
        $this->assertSame('2.5', $material->emissionFactorFor(2024)?->newFactor()->co2eq());
        $this->assertSame('3.0', $material->emissionFactorFor(2025)?->newFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $material = $this->material();

        $this->expectException(\InvalidArgumentException::class);

        $material->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '1.0'), 'recycled' => $this->emissionFactor(2024, '1.0')],
            ['new' => $this->emissionFactor(2024, '2.0'), 'recycled' => $this->emissionFactor(2024, '2.0')],
        ]);
    }

    public function testUpdatingMaterialUnitRestampsEmissionFactors(): void
    {
        $material = $this->material();
        $material->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $material->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $material->emissionFactorFor(2023)?->newFactor()->unit());

        $material->update('material-name', MaterialCategory::Metal, Unit::Piece);

        $factor2023 = $material->emissionFactorFor(2023);
        $factor2024 = $material->emissionFactorFor(2024);

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->newFactor()->unit());
        $this->assertSame(Unit::Piece, $factor2024->newFactor()->unit());
        // Other fields are preserved.
        $this->assertSame('1.0', $factor2023->newFactor()->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $material = $this->material();
        $material->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $material->removeEmissionFactor(2024);

        $this->assertCount(0, $material->emissionFactors());
    }

    private function material(): Material
    {
        return new Material(
            id: MaterialId::generate(),
            name: 'material-name',
            category: MaterialCategory::Metal,
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
