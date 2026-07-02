<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class EmissionFactorGroupTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
        );

        $this->assertCount(0, $emissionFactorGroup->translations());
        $emissionFactorGroup->translate('translated-name', Locale::English);
        $this->assertCount(1, $emissionFactorGroup->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
        );
        $emissionFactorGroup->translate('translated-name-en', Locale::English);
        $emissionFactorGroup->translate('translated-name-it', Locale::Italian);
        $emissionFactorGroup->translate('translated-name-ge', Locale::German);

        $this->assertEquals('group-name', $emissionFactorGroup->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
        );
        $emissionFactorGroup->translate('translated-name-en', Locale::English);
        $emissionFactorGroup->translate('translated-name-it', Locale::Italian);
        $emissionFactorGroup->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $emissionFactorGroup->name());
    }

    public function testCo2eqForComputesUsingOwnGwpValues(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
            gwp: new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
        );

        $co2eq = $emissionFactorGroup->co2eqFor(co2: '2', ch4: '1', n2o: '0.5');

        $this->assertSame((string) (2.0 * 1.0 + 1.0 * 27.9 + 0.5 * 273.0), $co2eq);
    }

    public function testBuildEmissionFactorUsesComputedCo2eq(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
            gwp: new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
        );

        $emissionFactor = $emissionFactorGroup->buildEmissionFactor(
            co2eq: '0',
            co2: '2',
            ch4: '1',
            n2o: '0.5',
            year: 2024,
            unit: Unit::Kilogram,
            source: 'test-source',
        );

        $this->assertSame($emissionFactorGroup->co2eqFor('2', '1', '0.5'), $emissionFactor->co2eq());
        $this->assertSame('2', $emissionFactor->co2());
        $this->assertSame('1', $emissionFactor->ch4());
        $this->assertSame('0.5', $emissionFactor->n2o());
        $this->assertSame(2024, $emissionFactor->year());
        $this->assertSame(Unit::Kilogram, $emissionFactor->unit());
        $this->assertSame('test-source', $emissionFactor->source());
    }

    public function testBuildEmissionFactorRespectsUserProvidedCo2eq(): void
    {
        $emissionFactorGroup = new EmissionFactorGroup(
            id: EmissionFactorGroupId::generate(),
            name: 'group-name',
            gwp: new Gwp(gwpCo2: 1.0, gwpCh4: 27.9, gwpN2O: 273.0),
        );

        $emissionFactor = $emissionFactorGroup->buildEmissionFactor(
            co2eq: '99',
            co2: '2',
            ch4: '1',
            n2o: '0.5',
            year: 2024,
            unit: Unit::Kilogram,
            source: 'test-source',
        );

        $this->assertSame('99', $emissionFactor->co2eq());
    }
}
