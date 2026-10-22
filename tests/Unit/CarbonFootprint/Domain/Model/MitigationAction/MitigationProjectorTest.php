<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use PHPUnit\Framework\TestCase;

class MitigationProjectorTest extends TestCase
{
    public function testExampleOfTheConceptualModel(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0), MitigationEmissionSourceMother::electricity(500.0)],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2)],
        );

        $this->assertNotNull($projection);
        $this->assertSame(2025, $projection->baseYear);
        $this->assertSame(2030, $projection->targetYear);
        $this->assertSame([2026, 2027, 2028, 2029, 2030], $projection->years);

        $gas = $projection->sources[0];
        $this->assertSame('natural-gas', $gas->emissionSource->itemId);
        $this->assertTrue($gas->hasReductions);
        $this->assertEqualsWithDelta(0.0436, $gas->rateByYear[2026], 0.0001);
        $this->assertEqualsWithDelta(1000.0, $gas->expectedByYear[2025], 0.001);
        $this->assertEqualsWithDelta(956.35, $gas->expectedByYear[2026], 0.01);
        $this->assertEqualsWithDelta(43.65, $gas->reductionByYear[2026], 0.01);
        $this->assertEqualsWithDelta(914.61, $gas->expectedByYear[2027], 0.01);
        $this->assertEqualsWithDelta(800.0, $gas->expectedByYear[2030], 0.001);
        $this->assertEqualsWithDelta(36.51, $gas->reductionByYear[2030], 0.01);

        $electricity = $projection->sources[1];
        $this->assertFalse($electricity->hasReductions);
        $this->assertEqualsWithDelta(500.0, $electricity->expectedByYear[2030], 0.001);

        $this->assertEqualsWithDelta(1500.0, $projection->totalByYear[2025], 0.001);
        $this->assertEqualsWithDelta(1456.35, $projection->totalByYear[2026], 0.01);
        $this->assertEqualsWithDelta(1300.0, $projection->totalByYear[2030], 0.001);
        $this->assertEqualsWithDelta(0.0291, $projection->interannualReductionByYear[2026], 0.0001);
        $this->assertEqualsWithDelta(0.0273, $projection->interannualReductionByYear[2030], 0.0001);
    }

    public function testOneYearHorizonUsesTheWholeReductionAsRate(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.3, 2025, 2026)],
        );

        $this->assertNotNull($projection);
        $this->assertSame([2026], $projection->years);
        $this->assertEqualsWithDelta(0.3, $projection->sources[0]->rateByYear[2026], 0.000001);
        $this->assertEqualsWithDelta(700.0, $projection->totalByYear[2026], 0.001);
    }

    public function testTwoActionsOnTheSameSourceAddTheirRates(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2, 2025, 2026),
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.1, 2025, 2026),
            ],
        );

        $this->assertNotNull($projection);
        $this->assertEqualsWithDelta(0.3, $projection->sources[0]->rateByYear[2026], 0.000001);
        $this->assertEqualsWithDelta(700.0, $projection->totalByYear[2026], 0.001);
    }

    public function testRateIsCappedAtOneHundredPercent(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.8, 2025, 2026),
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.7, 2025, 2026),
            ],
        );

        $this->assertNotNull($projection);
        $this->assertSame(1.0, $projection->sources[0]->rateByYear[2026]);
        $this->assertEqualsWithDelta(0.0, $projection->totalByYear[2026], 0.000001);
    }

    public function testActionEndingBeforeTheHorizonKeepsTheReduction(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0), MitigationEmissionSourceMother::electricity(500.0)],
            [
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2, 2025, 2026),
                MitigationActionMother::withEffect(Source::BuildingElectricity, 'marketer', 0.5, 2025, 2028),
            ],
        );

        $this->assertNotNull($projection);
        $this->assertSame(2028, $projection->targetYear);
        $gas = $projection->sources[0];
        $this->assertEqualsWithDelta(800.0, $gas->expectedByYear[2026], 0.001);
        $this->assertSame(0.0, $gas->rateByYear[2027]);
        $this->assertEqualsWithDelta(800.0, $gas->expectedByYear[2028], 0.001);
    }

    public function testImportedActionWithAnEarlierBaseYear(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2, 2023, 2027)],
        );

        $this->assertNotNull($projection);
        $this->assertSame([2026, 2027], $projection->years);
        $rate = 1 - 0.8 ** (1 / 4);
        $this->assertEqualsWithDelta($rate, $projection->sources[0]->rateByYear[2026], 0.000001);
        $this->assertEqualsWithDelta(1000.0 * (1 - $rate) ** 2, $projection->totalByYear[2027], 0.001);
    }

    public function testEffectOnASourceWithoutEmissionsDoesNotContribute(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [
                MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2, 2025, 2026),
                MitigationActionMother::withEffect(Source::RefrigerantLeaks, 'r410', 0.5, 2025, 2030),
            ],
        );

        $this->assertNotNull($projection);
        $this->assertSame(2026, $projection->targetYear);
        $this->assertCount(1, $projection->sources);
    }

    public function testWithoutActionsReturnsNull(): void
    {
        $this->assertNull((new MitigationProjector())->project(2025, [MitigationEmissionSourceMother::naturalGas()], []));
    }

    public function testWithoutEffectsReturnsNull(): void
    {
        $this->assertNull((new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas()],
            [MitigationActionMother::manual()],
        ));
    }

    public function testTargetYearNotAfterTheFootprintYearReturnsNull(): void
    {
        $this->assertNull((new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas()],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2, 2020, 2024)],
        ));
    }

    public function testInterannualReductionIsZeroWhenThePreviousTotalIsZero(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::naturalGas(1000.0)],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 1.0, 2025, 2027)],
        );

        $this->assertNotNull($projection);
        $this->assertEqualsWithDelta(0.0, $projection->totalByYear[2026], 0.000001);
        $this->assertSame(0.0, $projection->interannualReductionByYear[2027]);
    }

    public function testSourcesAreOrderedByScopeSourceAndItemName(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [
                MitigationEmissionSourceMother::from(Source::Investments, 'sector', 10.0, 'Sector'),
                MitigationEmissionSourceMother::electricity(),
                MitigationEmissionSourceMother::from(Source::RefrigerantLeaks, 'r410', 10.0, 'R410A'),
                MitigationEmissionSourceMother::from(Source::FixedFacilities, 'propane', 10.0, 'Propano'),
                MitigationEmissionSourceMother::from(Source::OwnVehicles, 'diesel', 10.0, 'Diésel'),
                MitigationEmissionSourceMother::naturalGas(),
            ],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2)],
        );

        $this->assertNotNull($projection);
        $this->assertSame(
            ['natural-gas', 'propane', 'diesel', 'r410', 'marketer', 'sector'],
            array_map(static fn($source) => $source->emissionSource->itemId, $projection->sources),
        );
    }

    public function testADerivedSourceFollowsTheReductionsOfItsOrigin(): void
    {
        $naturalGas = MitigationEmissionSourceMother::naturalGas(1000.0);
        $electricity = MitigationEmissionSourceMother::electricity(500.0);

        $projection = (new MitigationProjector())->project(
            2025,
            [
                $naturalGas,
                $electricity,
                MitigationEmissionSourceMother::derivedFrom($naturalGas, 100.0),
                MitigationEmissionSourceMother::derivedFrom($electricity, 50.0),
            ],
            [MitigationActionMother::withEffect(Source::FixedFacilities, 'natural-gas', 0.2)],
        );

        $this->assertNotNull($projection);

        $derivedNaturalGas = $projection->sources[3];
        $this->assertSame(Source::FuelAndEnergyRelated, $derivedNaturalGas->emissionSource->source);
        $this->assertSame('natural-gas', $derivedNaturalGas->emissionSource->itemId);
        $this->assertTrue($derivedNaturalGas->hasReductions);
        $this->assertEqualsWithDelta($projection->sources[0]->rateByYear[2026], $derivedNaturalGas->rateByYear[2026], 0.000001);
        $this->assertEqualsWithDelta(80.0, $derivedNaturalGas->expectedByYear[2030], 0.001);

        $derivedElectricity = $projection->sources[2];
        $this->assertFalse($derivedElectricity->hasReductions);
        $this->assertEqualsWithDelta(50.0, $derivedElectricity->expectedByYear[2030], 0.001);

        $this->assertEqualsWithDelta(1650.0, $projection->totalByYear[2025], 0.001);
        $this->assertEqualsWithDelta(1430.0, $projection->totalByYear[2030], 0.001);
    }

    public function testAnEffectOnADerivedSourceDoesNotReduceIt(): void
    {
        $naturalGas = MitigationEmissionSourceMother::naturalGas(1000.0);

        $projection = (new MitigationProjector())->project(
            2025,
            [$naturalGas, MitigationEmissionSourceMother::derivedFrom($naturalGas, 100.0)],
            [MitigationActionMother::withEffect(Source::FuelAndEnergyRelated, 'natural-gas', 0.5)],
        );

        $this->assertNull($projection);
    }

    public function testAnEffectOnAFranchiseReducesIt(): void
    {
        $projection = (new MitigationProjector())->project(
            2025,
            [MitigationEmissionSourceMother::from(Source::Franchises, 'franchised-1', 5000.0, 'Hotel Franquiciado')],
            [MitigationActionMother::withEffect(Source::Franchises, 'franchised-1', 0.2)],
        );

        $this->assertNotNull($projection);
        $this->assertTrue($projection->sources[0]->hasReductions);
        $this->assertEqualsWithDelta(4000.0, $projection->totalByYear[2030], 0.001);
    }
}
