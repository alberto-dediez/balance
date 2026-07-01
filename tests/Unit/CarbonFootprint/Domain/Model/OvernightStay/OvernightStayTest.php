<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class OvernightStayTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testUnitIsAlwaysPernoctacion(): void
    {
        $overnightStay = new OvernightStay(id: OvernightStayId::generate());

        $this->assertSame(Unit::Pernoctacion, $overnightStay->unit());
    }

    public function testTranslateAddTranslation(): void
    {
        $overnightStay = new OvernightStay(
            id: OvernightStayId::generate(),
            name: 'overnight-stay-name',
        );

        $this->assertCount(0, $overnightStay->translations());
        $overnightStay->translate('translated-name', Locale::English);
        $this->assertCount(1, $overnightStay->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $overnightStay = new OvernightStay(
            id: OvernightStayId::generate(),
            name: 'overnight-stay-name',
        );
        $overnightStay->translate('translated-name-en', Locale::English);
        $overnightStay->translate('translated-name-it', Locale::Italian);

        $this->assertEquals('overnight-stay-name', $overnightStay->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $overnightStay = new OvernightStay(
            id: OvernightStayId::generate(),
            name: 'overnight-stay-name',
        );
        $overnightStay->translate('translated-name-en', Locale::English);
        $overnightStay->translate('translated-name-it', Locale::Italian);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $overnightStay->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $overnightStay = $this->overnightStay();

        $overnightStay->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $overnightStay->emissionFactors());
        $this->assertSame('1.5', $overnightStay->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $overnightStay = $this->overnightStay();

        $overnightStay->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $overnightStay->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $overnightStay->emissionFactors());
        $this->assertSame('2.0', $overnightStay->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $overnightStay = $this->overnightStay();

        $this->assertNull($overnightStay->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $overnightStay = $this->overnightStay();
        $overnightStay->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $overnightStay->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $overnightStay->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $overnightStay->emissionFactors());
        $this->assertNull($overnightStay->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $overnightStay->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $overnightStay->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $overnightStay = $this->overnightStay();

        $this->expectException(\InvalidArgumentException::class);

        $overnightStay->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $overnightStay = $this->overnightStay();
        $overnightStay->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $overnightStay->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $overnightStay->emissionFactors());
    }

    private function overnightStay(): OvernightStay
    {
        return new OvernightStay(
            id: OvernightStayId::generate(),
            name: 'overnight-stay-name',
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
            unit: Unit::Pernoctacion,
            source: 'test-source',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
