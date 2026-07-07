<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MerchandisingItemTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $merchandisingItem = new MerchandisingItem(
            id: MerchandisingItemId::generate(),
            name: 'merchandising-item-name',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        );

        $this->assertCount(0, $merchandisingItem->translations());
        $merchandisingItem->translate('translated-name', Locale::English);
        $this->assertCount(1, $merchandisingItem->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $merchandisingItem = new MerchandisingItem(
            id: MerchandisingItemId::generate(),
            name: 'merchandising-item-name',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        );
        $merchandisingItem->translate('translated-name-en', Locale::English);
        $merchandisingItem->translate('translated-name-it', Locale::Italian);
        $merchandisingItem->translate('translated-name-ge', Locale::German);

        $this->assertEquals('merchandising-item-name', $merchandisingItem->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $merchandisingItem = new MerchandisingItem(
            id: MerchandisingItemId::generate(),
            name: 'merchandising-item-name',
            category: MerchandisingItemCategory::Merchandising,
            unit: Unit::Kilogram,
        );
        $merchandisingItem->translate('translated-name-en', Locale::English);
        $merchandisingItem->translate('translated-name-it', Locale::Italian);
        $merchandisingItem->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $merchandisingItem->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $merchandisingItem = $this->merchandisingItem();

        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $merchandisingItem->emissionFactors());
        $this->assertSame('1.5', $merchandisingItem->emissionFactorFor(2024, $this->groupId())?->newFactor()->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $merchandisingItem = $this->merchandisingItem();

        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $merchandisingItem->emissionFactors());
        $this->assertSame('2.0', $merchandisingItem->emissionFactorFor(2024, $this->groupId())?->newFactor()->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $merchandisingItem = $this->merchandisingItem();

        $this->assertNull($merchandisingItem->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $merchandisingItem = $this->merchandisingItem();
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $merchandisingItem->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '2.5'), 'recycled' => $this->emissionFactor(2024, '2.5')],
            ['new' => $this->emissionFactor(2025, '3.0'), 'recycled' => $this->emissionFactor(2025, '3.0')],
        ]);

        $this->assertCount(2, $merchandisingItem->emissionFactors());
        $this->assertNull($merchandisingItem->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $merchandisingItem->emissionFactorFor(2024, $this->groupId())?->newFactor()->co2eq());
        $this->assertSame('3.0', $merchandisingItem->emissionFactorFor(2025, $this->groupId())?->newFactor()->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $merchandisingItem = $this->merchandisingItem();

        $this->expectException(\InvalidArgumentException::class);

        $merchandisingItem->syncEmissionFactors([
            ['new' => $this->emissionFactor(2024, '1.0'), 'recycled' => $this->emissionFactor(2024, '1.0')],
            ['new' => $this->emissionFactor(2024, '2.0'), 'recycled' => $this->emissionFactor(2024, '2.0')],
        ]);
    }

    public function testUpdatingUnitRestampsEmissionFactors(): void
    {
        $merchandisingItem = $this->merchandisingItem();
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2023, '1.0'), $this->emissionFactor(2023, '1.0'));
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '2.0'), $this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $merchandisingItem->emissionFactorFor(2023, $this->groupId())?->newFactor()->unit());

        $merchandisingItem->update('merchandising-item-name', MerchandisingItemCategory::Merchandising, Unit::Piece);

        $factor2023 = $merchandisingItem->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $merchandisingItem->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->newFactor()->unit());
        $this->assertSame(Unit::Piece, $factor2024->newFactor()->unit());
        $this->assertSame('1.0', $factor2023->newFactor()->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $merchandisingItem = $this->merchandisingItem();
        $merchandisingItem->setEmissionFactor($this->emissionFactor(2024, '1.5'), $this->emissionFactor(2024, '1.5'));

        $merchandisingItem->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $merchandisingItem->emissionFactors());
    }

    private function merchandisingItem(): MerchandisingItem
    {
        return new MerchandisingItem(
            id: MerchandisingItemId::generate(),
            name: 'merchandising-item-name',
            category: MerchandisingItemCategory::Merchandising,
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
            emissionFactorGroupId: $this->groupId(),
            unit: Unit::Kilogram,
            source: 'test-source',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
