<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class CateringItemTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $cateringItem = new CateringItem(
            id: CateringItemId::generate(),
            name: 'catering-item-name',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $cateringItem->translations());
        $cateringItem->translate('translated-name', Locale::English);
        $this->assertCount(1, $cateringItem->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $cateringItem = new CateringItem(
            id: CateringItemId::generate(),
            name: 'catering-item-name',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        );
        $cateringItem->translate('translated-name-en', Locale::English);
        $cateringItem->translate('translated-name-it', Locale::Italian);
        $cateringItem->translate('translated-name-ge', Locale::German);

        $this->assertEquals('catering-item-name', $cateringItem->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $cateringItem = new CateringItem(
            id: CateringItemId::generate(),
            name: 'catering-item-name',
            category: CateringItemCategory::Breakfast,
            unit: Unit::Kilogram,
        );
        $cateringItem->translate('translated-name-en', Locale::English);
        $cateringItem->translate('translated-name-it', Locale::Italian);
        $cateringItem->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $cateringItem->name());
    }

    public function testRegisterStoresUnitWeight(): void
    {
        $cateringItem = new CateringItem(id: CateringItemId::generate());

        $cateringItem->register('t-shirt', CateringItemCategory::Drink, Unit::Piece, '0.25');

        $this->assertSame('0.25', $cateringItem->unitWeight());
        $this->assertSame(Unit::Piece, $cateringItem->unit());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $cateringItem = $this->cateringItem();

        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $cateringItem->emissionFactors());
        $this->assertSame('1.5', $cateringItem->emissionFactorFor(2024)?->newFactor()->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $cateringItem = $this->cateringItem();

        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));
        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $cateringItem->emissionFactors());
        $this->assertSame('2.0', $cateringItem->emissionFactorFor(2024)?->newFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $cateringItem = $this->cateringItem();

        $this->assertNull($cateringItem->emissionFactorFor(1999));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $cateringItem = $this->cateringItem();
        $cateringItem->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $cateringItem->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '2.5'), 'recycled' => $this->emissionFactor(2024, '2.5')],
            ['new' => $this->emissionFactor(2025, '3.0'), 'recycled' => $this->emissionFactor(2025, '3.0')],
        ]);

        $this->assertCount(2, $cateringItem->emissionFactors());
        $this->assertNull($cateringItem->emissionFactorFor(2023));
        $this->assertSame('2.5', $cateringItem->emissionFactorFor(2024)?->newFactor()->co2eq());
        $this->assertSame('3.0', $cateringItem->emissionFactorFor(2025)?->newFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $cateringItem = $this->cateringItem();

        $this->expectException(\InvalidArgumentException::class);

        $cateringItem->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '1.0'), 'recycled' => $this->emissionFactor(2024, '1.0')],
            ['new' => $this->emissionFactor(2024, '2.0'), 'recycled' => $this->emissionFactor(2024, '2.0')],
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $cateringItem = $this->cateringItem();
        $cateringItem->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $cateringItem->emissionFactorFor(2023)?->newFactor()->unit());

        $cateringItem->update('catering-item-name', CateringItemCategory::Breakfast, Unit::Piece);

        $factor2023 = $cateringItem->emissionFactorFor(2023);
        $factor2024 = $cateringItem->emissionFactorFor(2024);

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->newFactor()->unit());
        $this->assertSame(Unit::Piece, $factor2024->newFactor()->unit());
        $this->assertSame('1.0', $factor2023->newFactor()->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $cateringItem = $this->cateringItem();
        $cateringItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $cateringItem->removeEmissionFactor(2024);

        $this->assertCount(0, $cateringItem->emissionFactors());
    }

    private function cateringItem(): CateringItem
    {
        return new CateringItem(
            id: CateringItemId::generate(),
            name: 'catering-item-name',
            category: CateringItemCategory::Breakfast,
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
            emissionFactorGroupId: EmissionFactorGroupMother::default()->id(),
            unit: Unit::Kilogram,
            source: 'test-source',
        );
    }
}
