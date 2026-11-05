<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRow;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowType;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\DataExchange\CarbonFootprintDetailCellsFormatter;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class CarbonFootprintDetailCellsFormatterTest extends TestCase
{
    private CarbonFootprintDetailCellsFormatter $sut;

    protected function setUp(): void
    {
        $this->sut = new CarbonFootprintDetailCellsFormatter(new class implements TranslatorInterface {
            /** @param array<string, mixed> $parameters */
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return null === $domain ? $id : sprintf('%s:%s', $domain, $id);
            }

            public function getLocale(): string
            {
                return 'es';
            }
        });
    }

    public function testDeclaresTheCommonColumnsWithTheStageColumnOfTheSubject(): void
    {
        $columns = $this->sut->commonColumns(new ExportColumnDefinition('phase', new TranslatableMessage('Fase')));

        self::assertSame(
            ['rowType', 'phase', 'source', 'property', 'activityType', 'item', 'origin', 'provider', 'dataSource', 'quantity', 'unit', 'condition', 'nights', 'people', 'weightTonnes', 'distanceKm', 'containerCapacityLitres', 'fillPercentage', 'acquisitionYear', 'factorCo2eq', 'factorUnit', 'factorYear', 'factorSource', 'emissionsCo2eq', 'emissionsCo2', 'emissionsCh4', 'emissionsN2o', 'wellToTankCo2eq', 'locationBasedCo2eq', 'manualCo2eq', 'exclusionReason', 'exclusionComment', 'materialityScorePercent', 'materialityThresholdPercent', 'materialityVerdict', 'materialityForcedByHighMagnitude', 'materialityOverride', 'registeredAt', 'updatedAt', 'activityId'],
            array_map(static fn(ExportColumnDefinition $column): string => $column->key, $columns),
        );
    }

    public function testLeavesOutTheExcludedColumns(): void
    {
        $columns = $this->sut->commonColumns(new ExportColumnDefinition('stage', new TranslatableMessage('Alcance')), ['property', 'manualCo2eq']);
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::Activity, Scope::Scope1), 'stage', 'scope', ['property', 'manualCo2eq']);

        $keys = array_map(static fn(ExportColumnDefinition $column): string => $column->key, $columns);

        self::assertNotContains('property', $keys);
        self::assertNotContains('manualCo2eq', $keys);
        self::assertSame($keys, array_keys($cells));
    }

    public function testFormatsTheCellsOfAnActivityRow(): void
    {
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(
            rowType: CarbonFootprintDetailRowType::Activity,
            stage: Scope::Scope1,
            source: Source::FixedFacilities,
            propertyName: 'Oficina',
            activityType: ActivityType::Combustible,
            itemName: 'Gasóleo',
            dataSource: 'Factura',
            quantity: 200.0,
            unit: Unit::Liter,
            condition: ItemCondition::Recycled,
            factorCo2eq: '1.5',
            factorUnit: Unit::Kilogram,
            factorYear: 2024,
            emissionsCo2eq: '300.0',
            registeredAt: new \DateTimeImmutable('2026-03-04 05:06'),
            activityId: 'activity-1',
        ), 'stage', 'scope');

        self::assertSame('carbonFootprintDetailRowType:activity', $cells['rowType']);
        self::assertSame('scope:scope_1', $cells['stage']);
        self::assertSame('source:fixed_facilities', $cells['source']);
        self::assertSame('Oficina', $cells['property']);
        self::assertSame('activityType:combustible', $cells['activityType']);
        self::assertSame('Gasóleo', $cells['item']);
        self::assertSame('Propia', $cells['origin']);
        self::assertSame('', $cells['provider']);
        self::assertSame('Factura', $cells['dataSource']);
        self::assertSame(200.0, $cells['quantity']);
        self::assertSame('unitType:l', $cells['unit']);
        self::assertSame('Reciclado', $cells['condition']);
        self::assertSame('1.5', $cells['factorCo2eq']);
        self::assertSame('unitType:kg', $cells['factorUnit']);
        self::assertSame(2024, $cells['factorYear']);
        self::assertSame('300.0', $cells['emissionsCo2eq']);
        self::assertSame('', $cells['nights']);
        self::assertSame('', $cells['exclusionReason']);
        self::assertSame('04/03/2026 05:06', $cells['registeredAt']);
        self::assertSame('', $cells['updatedAt']);
        self::assertSame('activity-1', $cells['activityId']);
    }

    public function testUsesTheStageColumnAndDomainOfTheSubject(): void
    {
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::Activity, Phase::Assembly, Source::FixedFacilities, condition: ItemCondition::New), 'phase', 'phase');

        self::assertSame('phase:assembly', $cells['phase']);
        self::assertArrayNotHasKey('stage', $cells);
        self::assertSame('Nuevo', $cells['condition']);
    }

    public function testDescribesTheOriginOfTheActivity(): void
    {
        $fromProvider = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::Activity, Scope::Scope3, Source::UpstreamTransport, providerName: 'Logística ACME'), 'stage', 'scope');
        $fromEmployees = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::DerivedFuelAndEnergy, Scope::Scope3, Source::FuelAndEnergyRelated, fromEmployees: true), 'stage', 'scope');
        $nonConsolidated = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::NonConsolidatedProperty, Scope::Scope3, Source::Franchises, manualCo2eq: '4500'), 'stage', 'scope');

        self::assertSame('Proveedor', $fromProvider['origin']);
        self::assertSame('Logística ACME', $fromProvider['provider']);
        self::assertSame('Empleados', $fromEmployees['origin']);
        self::assertSame('', $nonConsolidated['origin']);
        self::assertSame('4500', $nonConsolidated['manualCo2eq']);
    }

    public function testFormatsTheExclusionOfTheWholeScope3(): void
    {
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(
            rowType: CarbonFootprintDetailRowType::Exclusion,
            stage: Scope::Scope3,
            exclusionReason: ExclusionReason::NoDataAvailable,
            exclusionComment: 'Sin datos',
        ), 'stage', 'scope');

        self::assertSame('Alcance 3 completo', $cells['source']);
        self::assertSame('', $cells['activityType']);
        self::assertSame('', $cells['origin']);
        self::assertSame('exclusionReason:no_data_available', $cells['exclusionReason']);
        self::assertSame('Sin datos', $cells['exclusionComment']);
    }

    public function testTheMaterialityColumnsAreOptionalAndUncheckedByDefault(): void
    {
        $columns = $this->sut->commonColumns(new ExportColumnDefinition('stage', new TranslatableMessage('Alcance')));
        $materiality = array_values(array_filter($columns, static fn(ExportColumnDefinition $column): bool => str_starts_with($column->key, 'materiality')));

        self::assertCount(5, $materiality);

        foreach ($materiality as $column) {
            self::assertFalse($column->selectedByDefault);
        }
    }

    public function testFormatsTheMaterialityOfAnExclusionRow(): void
    {
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(
            rowType: CarbonFootprintDetailRowType::Exclusion,
            stage: Scope::Scope3,
            source: Source::SoldProductsProcessing,
            exclusionReason: ExclusionReason::NotSignificant,
            materialityScorePercent: 16.67,
            materialityThresholdPercent: 50,
            materialityVerdict: MaterialityVerdict::Omit,
            materialityForcedByHighMagnitude: false,
            materialityOverride: 'agarcia · 14/03/2026 · Exigencia del cliente principal',
        ), 'stage', 'scope');

        self::assertSame(16.67, $cells['materialityScorePercent']);
        self::assertSame(50, $cells['materialityThresholdPercent']);
        self::assertSame('materialityVerdictType:omit', $cells['materialityVerdict']);
        self::assertSame('No', $cells['materialityForcedByHighMagnitude']);
        self::assertSame('agarcia · 14/03/2026 · Exigencia del cliente principal', $cells['materialityOverride']);
    }

    public function testTheMaterialityCellsAreEmptyWhenTheRowHasNoDecision(): void
    {
        $cells = $this->sut->cells(new CarbonFootprintDetailRow(CarbonFootprintDetailRowType::Activity, Scope::Scope3, Source::BusinessTravel), 'stage', 'scope');

        foreach (['materialityScorePercent', 'materialityThresholdPercent', 'materialityVerdict', 'materialityForcedByHighMagnitude', 'materialityOverride'] as $key) {
            self::assertSame('', $cells[$key]);
        }
    }
}
