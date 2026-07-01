<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class WasteTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
            unit: Unit::Kilogram,
            density: 1.0,
        );

        $this->assertCount(0, $waste->translations());
        $waste->translate('translated-name', Locale::English);
        $this->assertCount(1, $waste->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
            unit: Unit::Kilogram,
            density: 1.0,
        );
        $waste->translate('translated-name-en', Locale::English);
        $waste->translate('translated-name-it', Locale::Italian);
        $waste->translate('translated-name-ge', Locale::German);

        $this->assertEquals('waste-name', $waste->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
            unit: Unit::Kilogram,
            density: 1.0,
        );
        $waste->translate('translated-name-en', Locale::English);
        $waste->translate('translated-name-it', Locale::Italian);
        $waste->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $waste->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $waste = $this->waste();

        $waste->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $waste->emissionFactors());
        $this->assertSame('1.5', $waste->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $waste = $this->waste();

        $waste->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $waste->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $waste->emissionFactors());
        $this->assertSame('2.0', $waste->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $waste = $this->waste();

        $this->assertNull($waste->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $waste = $this->waste();
        $waste->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $waste->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $waste->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $waste->emissionFactors());
        $this->assertNull($waste->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $waste->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $waste->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $waste = $this->waste();

        $this->expectException(\InvalidArgumentException::class);

        $waste->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingWasteUnitRestampsEmissionFactors(): void
    {
        $waste = $this->waste();
        $waste->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $waste->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $waste->emissionFactorFor(2023, $this->groupId())?->unit());

        $waste->update('waste-name', Unit::Piece, 1.0);

        $factor2023 = $waste->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $waste->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testRemoveEmissionFactor(): void
    {
        $waste = $this->waste();
        $waste->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $waste->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $waste->emissionFactors());
    }

    public function testDensityIsPreserved(): void
    {
        $waste = new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
            unit: Unit::Kilogram,
            density: 2.5,
        );

        $this->assertSame(2.5, $waste->density());
    }

    public function testRegisterStoresDensity(): void
    {
        $waste = new Waste(id: WasteId::generate());
        $waste->register('waste-name', Unit::Kilogram, 3.14);

        $this->assertSame(3.14, $waste->density());
    }

    public function testUpdateStoresDensity(): void
    {
        $waste = new Waste(id: WasteId::generate());
        $waste->register('waste-name', Unit::Kilogram, 1.0);
        $waste->update('new-name', Unit::Kilogram, 4.2);

        $this->assertSame(4.2, $waste->density());
    }

    private function waste(): Waste
    {
        return new Waste(
            id: WasteId::generate(),
            name: 'waste-name',
            unit: Unit::Kilogram,
            density: 1.0,
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
