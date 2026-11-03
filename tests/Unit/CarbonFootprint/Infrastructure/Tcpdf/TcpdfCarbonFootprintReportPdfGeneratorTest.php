<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Tcpdf\ClassicTcpdfCarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Infrastructure\Tcpdf\TcpdfCarbonFootprintReportPdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Tcpdf\PdfKeyFigure;
use App\Shared\Infrastructure\Theme\GreenhostTheme;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnexMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;

final class TcpdfCarbonFootprintReportPdfGeneratorTest extends TestCase
{
    private const string PREVIEW_DIRECTORY = 'var/carbon-footprint-report-preview';

    /** @return iterable<string, array{0: string, 1: string, 2: Locale}> */
    public static function previews(): iterable
    {
        foreach (['default', 'classic', 'greenhost'] as $theme) {
            yield $theme . ' event es' => [$theme, 'event', Locale::Spanish];
            yield $theme . ' event en' => [$theme, 'event', Locale::English];
            yield $theme . ' event electricity without mix es' => [$theme, 'event-electricity-without-mix', Locale::Spanish];
            yield $theme . ' property es' => [$theme, 'property', Locale::Spanish];
            yield $theme . ' company es' => [$theme, 'company', Locale::Spanish];
            yield $theme . ' property mitigation es' => [$theme, 'property-mitigation', Locale::Spanish];
            yield $theme . ' property mitigation en' => [$theme, 'property-mitigation', Locale::English];
            yield $theme . ' company mitigation es' => [$theme, 'company-mitigation', Locale::Spanish];
            yield $theme . ' property long mitigation es' => [$theme, 'property-long-mitigation', Locale::Spanish];
            yield $theme . ' property scope 3 es' => [$theme, 'property-scope-3', Locale::Spanish];
            yield $theme . ' company scope 3 es' => [$theme, 'company-scope-3', Locale::Spanish];
            yield $theme . ' property scope 3 excluded es' => [$theme, 'property-scope-3-excluded', Locale::Spanish];
            yield $theme . ' company non consolidated properties es' => [$theme, 'company-non-consolidated', Locale::Spanish];
            yield $theme . ' property precalculated es' => [$theme, 'property-precalculated', Locale::Spanish];
            yield $theme . ' property mixed methods es' => [$theme, 'property-mixed-methods', Locale::Spanish];
            yield $theme . ' property mixed methods en' => [$theme, 'property-mixed-methods', Locale::English];
            yield $theme . ' company boundaries es' => [$theme, 'company-boundaries', Locale::Spanish];
            yield $theme . ' property materiality es' => [$theme, 'property-materiality', Locale::Spanish];
            yield $theme . ' property materiality en' => [$theme, 'property-materiality', Locale::English];
            yield $theme . ' company materiality override es' => [$theme, 'company-materiality-override', Locale::Spanish];
            yield $theme . ' property scope 3 excluded materiality es' => [$theme, 'property-scope-3-excluded-materiality', Locale::Spanish];
            yield $theme . ' property materiality reopened es' => [$theme, 'property-materiality-reopened', Locale::Spanish];
        }
    }

    /** @dataProvider previews */
    public function testItDrawsAReport(string $theme, string $type, Locale $locale): void
    {
        [$data, $texts] = $this->fixtureFor($type);

        $path = $this->generatorFor($theme)->generate($data, $texts, CarbonFootprintReportChartsMother::withFixturePng(), $locale, $theme);

        try {
            $this->assertFileExists($path);

            $contents = (string) file_get_contents($path);

            $this->assertStringStartsWith('%PDF-', $contents);
            $this->assertStringEndsWith("%%EOF\n", $contents);
            $this->assertGreaterThanOrEqual(8, preg_match_all('#/Type /Page[^s]#', $contents));

            copy($path, $this->previewPath($theme, $type . '-' . $locale->value));
        } finally {
            @unlink($path);
        }
    }

    /** @return iterable<string, array{0: string}> */
    public static function themes(): iterable
    {
        yield 'default' => ['default'];
        yield 'classic' => ['classic'];
    }

    /** @dataProvider themes */
    public function testOnlyTheScope3SourcesWithDataHaveTheirOwnPage(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withScope3OnlyInPurchasedGoodsAndServices());

        $this->assertSame([Source::PurchasedGoodsAndServices], $sources);
    }

    /** @dataProvider themes */
    public function testExcludedScope3SourcesKeepTheirOwnPage(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withScope3OnlyInPurchasedGoodsAndServices(Source::BusinessTravel));

        $this->assertEqualsCanonicalizing([Source::PurchasedGoodsAndServices, Source::BusinessTravel], $sources);
    }

    /** @dataProvider themes */
    public function testTheNonConsolidatedPropertiesHaveTheirOwnPageInACompanyReport(string $theme): void
    {
        $sources = $this->scope3PageSources($theme, OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties());

        $this->assertContains(Source::DownstreamLeasedAssets, $sources);
        $this->assertContains(Source::Franchises, $sources);

        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'nonConsolidatedPropertyTable'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), Source::DownstreamLeasedAssets, Locale::Spanish);

        $this->assertCount(2, $table[1]);
        $this->assertSame('Hotel Arrendado', $table[1][0][0]);
        $this->assertTrue($table[3]);
    }

    /** @dataProvider themes */
    public function testTheOriginOfAnOperatorContributionLineIsShown(string $theme): void
    {
        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'nonConsolidatedPropertyTable'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(true), Source::Franchises, Locale::Spanish);

        $this->assertSame('Hotel Franquicia', $table[1][0][0]);
        $this->assertSame($this->translator()->trans('Aportación del franquiciado o arrendatario', [], null, Locale::Spanish->value), $table[1][0][1]);
    }

    /** @dataProvider themes */
    public function testAPropertyReportLeavesOutTheCompanyOnlySources(string $theme): void
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'includedEmissionsRows'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::Spanish);
        /** @var list<list<string>> $companyRows */
        $companyRows = (new \ReflectionMethod($generator, 'includedEmissionsRows'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::withScope3(CarbonFootprintReportSubjectType::Company), Locale::Spanish);

        $franchises = 'Cat. 14 · ' . $this->translator()->trans(Source::Franchises->value, [], 'source', Locale::Spanish->value);
        $leasedAssets = 'Cat. 13 · ' . $this->translator()->trans(Source::DownstreamLeasedAssets->value, [], 'source', Locale::Spanish->value);

        $this->assertNotContains($franchises, array_column($rows, 1));
        $this->assertNotContains($leasedAssets, array_column($rows, 1));
        $this->assertContains($franchises, array_column($companyRows, 1));
        $this->assertCount(count($rows) + 2, $companyRows);
    }

    /** @dataProvider themes */
    public function testTheEventElectricityTableListsOneRowPerElectricityActivityPlusTheTotal(string $theme): void
    {
        $withCompensation = $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::withCompensation());

        $this->assertCount(3, $withCompensation);
        $this->assertSame('400,00', $withCompensation[1][5]);
        $this->assertCount(0, $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::random()));

        $withoutMix = $this->eventElectricityTableRows($theme, EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertSame('---', $withoutMix[1][5]);
    }

    /** @dataProvider themes */
    public function testTheEventTotalsIncludeTheDualReportingRowsOnlyWithElectricity(string $theme): void
    {
        $this->assertSame([], $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::random()));
        $this->assertCount(3, $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::withCompensation()));

        $withoutMix = $this->eventDualReportingRows($theme, EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertCount(4, $withoutMix);
        $this->assertSame(['Actividades de electricidad sin factor de mix eléctrico', '1'], $withoutMix[3]);
    }

    /** @dataProvider themes */
    public function testTheOrganizationalTotalsIncludeTheDualReportingRows(string $theme): void
    {
        $rows = $this->organizationalDualReportingRows($theme, OrganizationalCarbonFootprintReportDataMother::forProperty());

        $this->assertCount(3, $rows);
        $this->assertSame('2.500,00', $rows[0][1]);
        $this->assertSame('2.000,00', $rows[1][1]);
        $this->assertSame('3.430,00', $rows[2][1]);

        $withoutMix = $this->organizationalDualReportingRows($theme, OrganizationalCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult());

        $this->assertCount(4, $withoutMix);
        $this->assertSame(['Actividades de electricidad sin factor de mix eléctrico', '1'], $withoutMix[3]);
    }

    /** @dataProvider themes */
    public function testAPrecalculatedReportListsTheReportedEmissionsOfTheFuelAndEnergyRelatedSource(string $theme): void
    {
        $table = $this->organizationalSourceTable($theme, OrganizationalCarbonFootprintReportDataMother::precalculated(), Scope::Scope3, Source::FuelAndEnergyRelated);

        $this->assertCount(2, $table[1]);
        $this->assertSame('Combustibles y energía comunicados', $table[1][0][0]);
        $this->assertSame('590,00', $table[1][1][5]);
    }

    /** @dataProvider themes */
    public function testAPrecalculatedReportListsTheReportedElectricityEmissionsWithoutTheElectricityColumns(string $theme): void
    {
        $table = $this->organizationalSourceTable($theme, OrganizationalCarbonFootprintReportDataMother::precalculated(), Scope::Scope2, Source::BuildingElectricity);

        $this->assertNotContains('kWh', $table[0]);
        $this->assertSame('Electricidad comunicada', $table[1][0][0]);
        $this->assertSame('2.500,00', $table[1][0][5]);
    }

    /** @dataProvider themes */
    public function testAReportNotPrecalculatedKeepsTheElectricityAndDerivedTables(string $theme): void
    {
        $electricity = $this->organizationalSourceTable($theme, OrganizationalCarbonFootprintReportDataMother::withScope3(), Scope::Scope2, Source::BuildingElectricity);
        $derived = $this->organizationalSourceTable($theme, OrganizationalCarbonFootprintReportDataMother::withScope3(), Scope::Scope3, Source::FuelAndEnergyRelated);

        $this->assertContains('kWh', $electricity[0]);
        $this->assertCount(3, $derived[1]);
        $this->assertSame('590,00', $derived[1][2][3]);
    }

    /** @dataProvider themes */
    public function testAPrecalculatedReportDoesNotDeclareALocationBasedScope2Figure(string $theme): void
    {
        $rows = $this->organizationalDualReportingRows($theme, OrganizationalCarbonFootprintReportDataMother::precalculated());

        $this->assertSame('---', $rows[1][1]);

        $generator = $this->generatorFor($theme);

        /** @var list<PdfKeyFigure> $figures */
        $figures = (new \ReflectionMethod($generator, 'organizationalKeyFigures'))->invoke($generator, OrganizationalCarbonFootprintReportDataMother::precalculated(), Locale::Spanish);
        $locationBased = array_values(array_filter($figures, static fn(PdfKeyFigure $figure): bool => 'Alcance 2 · localización' === $figure->label));

        $this->assertCount(1, $locationBased);
        $this->assertSame('---', $locationBased[0]->value);
    }

    /** @dataProvider themes */
    public function testTheScope3SourcesAreLabelledWithTheirGhgProtocolCategory(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'scopeSourceRows', OrganizationalCarbonFootprintReportDataMother::withScope3());
        $purchased = $this->translator()->trans(Source::PurchasedGoodsAndServices->value, [], 'source', Locale::Spanish->value);
        $fixedFacilities = $this->translator()->trans(Source::FixedFacilities->value, [], 'source', Locale::Spanish->value);

        $this->assertContains('Cat. 1 · ' . $purchased, array_column($rows, 1));
        $this->assertContains($fixedFacilities, array_column($rows, 1));
    }

    /** @dataProvider themes */
    public function testTheResultsTablesShowThePercentageOfTheTotal(string $theme): void
    {
        $scopeRows = $this->rowsOf($theme, 'scopeRows', OrganizationalCarbonFootprintReportDataMother::forProperty());

        $this->assertSame(['1.430,00', '36,39 %'], array_slice($scopeRows[0], 1));
        $this->assertSame('63,61 %', $scopeRows[1][2]);
    }

    /** @dataProvider themes */
    public function testTheCategoryMethodsTableSplitsTheEmissionsOfEachCategoryByMethod(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'categoryMethodRows', OrganizationalCarbonFootprintReportDataMother::withMixedMethods());
        $purchased = array_values(array_filter($rows, static fn(array $row): bool => str_starts_with($row[1], 'Cat. 1 · ')));

        $this->assertCount(2, $purchased);
        $this->assertSame(['Basado en gasto', '3.000,00', '71,43 %'], array_slice($purchased[0], 2));
        $this->assertSame(['Específico del proveedor', '1.200,00', '28,57 %'], array_slice($purchased[1], 2));
        $this->assertContains('Derivado: emisiones del pozo al tanque (WTT)', array_column($rows, 2));
        $this->assertContains('Basado en distancia', array_column($rows, 2));
        $this->assertContains('Específico por tipo de residuo', array_column($rows, 2));
    }

    /** @dataProvider themes */
    public function testTheDataQualityTableGivesTheWorstQualityAndTheMarginOfErrorOfTheSurvey(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'dataQualityRows', OrganizationalCarbonFootprintReportDataMother::withMixedMethods());
        $byCategory = array_column($rows, null, 1);
        $purchased = 'Cat. 1 · ' . $this->translator()->trans(Source::PurchasedGoodsAndServices->value, [], 'source', Locale::Spanish->value);
        $commuting = 'Cat. 7 · ' . $this->translator()->trans(Source::EmployeeCommuting->value, [], 'source', Locale::Spanish->value);

        $this->assertSame('Baja', $byCategory[$purchased][2]);
        $this->assertSame('Media', $byCategory[$commuting][2]);
        $this->assertSame('±6,50 % (95 %)', $byCategory[$commuting][3]);
    }

    /** @dataProvider themes */
    public function testTheDataQualityTableWarnsWhenThereIsNoSurveyData(string $theme): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withMixedMethods();
        $rows = $this->rowsOf($theme, 'dataQualityRows', new OrganizationalCarbonFootprintReportData(
            subjectType: $data->subjectType,
            subjectName: $data->subjectName,
            companyName: $data->companyName,
            countryName: $data->countryName,
            emissionFactorGroupName: $data->emissionFactorGroupName,
            calculationYear: $data->calculationYear,
            totalEmissions: $data->totalEmissions,
            locationBasedElectricityEmissions: $data->locationBasedElectricityEmissions,
            locationBasedTotalEmissions: $data->locationBasedTotalEmissions,
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $data->emissionsBySource,
            emissionsByScope: $data->emissionsByScope,
            emissionsByScopeAndSource: $data->emissionsByScopeAndSource,
            emissionsByProperty: $data->emissionsByProperty,
            activityLines: $data->activityLines,
        ));

        $this->assertContains('Sin datos de encuesta', array_column($rows, 3));
    }

    /** @dataProvider themes */
    public function testTheBoundaryTableListsEveryPropertyWithItsRegimeAndTreatment(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'boundaryPropertyRows', OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines());
        $byProperty = array_column($rows, null, 0);

        $this->assertCount(4, $rows);
        $this->assertSame('Consolidada: alcances 1 y 2', $byProperty['Hotel Sol'][2]);
        $this->assertSame('Consolidada: alcances 1 y 2', $byProperty['Hotel Luna'][2]);
        $this->assertStringContainsString('Cat. 14 · ', $byProperty['Hotel Franquicia'][2]);
        $this->assertStringContainsString('Cat. 13 · ', $byProperty['Hotel Arrendado'][2]);
        $this->assertSame($this->translator()->trans('franchised', [], 'operatingRegime', Locale::Spanish->value), $byProperty['Hotel Franquicia'][1]);
        $this->assertSame([], $this->rowsOf($theme, 'boundaryPropertyRows', OrganizationalCarbonFootprintReportDataMother::forProperty()));
    }

    /** @dataProvider themes */
    public function testTheBoundaryRowsDeclareTheOperationalControlApproach(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'boundaryRows', OrganizationalCarbonFootprintReportDataMother::forProperty());

        $this->assertContains(['Enfoque de consolidación', 'Control operacional'], $rows);
    }

    /** @dataProvider themes */
    public function testTheEmissionFactorSourcesTableSummarisesEachSource(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'emissionFactorSourceRows', OrganizationalCarbonFootprintReportDataMother::withMixedMethods());
        $bySource = array_column($rows, null, 0);

        $this->assertSame(['MITECO', '2025', '2', '1.430,00'], $bySource['MITECO']);
        $this->assertSame(['DEFRA', '2024', '1', '900,00'], $bySource['DEFRA']);
        $this->assertSame('1', $bySource['Sin fuente declarada'][2]);
        $this->assertSame(['Derivado: emisiones del pozo al tanque (WTT)', '-', '2', '590,00'], $bySource['Derivado: emisiones del pozo al tanque (WTT)']);
    }

    /** @dataProvider themes */
    public function testTheEmissionFactorSourcesTableHasARowPerMethodOfTheNonConsolidatedProperties(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'emissionFactorSourceRows', OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines());
        $bySource = array_column($rows, null, 0);

        $this->assertSame(['Huella de la propiedad', '-', '1', '5.000,00'], $bySource['Huella de la propiedad']);
        $this->assertSame(['Valor manual', '-', '1', '4.500,00'], $bySource['Valor manual']);
        $this->assertArrayNotHasKey('Aportación del franquiciado o arrendatario', $bySource);
    }

    /** @dataProvider themes */
    public function testTheBoundaryTableLeavesOutAPropertyWhoseCategoryIsExcluded(string $theme): void
    {
        $byProperty = array_column($this->rowsOf($theme, 'boundaryPropertyRows', OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(Source::Franchises)), null, 0);

        $this->assertSame('Fuera del cálculo', $byProperty['Hotel Franquicia'][2]);
        $this->assertStringContainsString('Cat. 13 · ', $byProperty['Hotel Arrendado'][2]);
        $this->assertSame('Consolidada: alcances 1 y 2', $byProperty['Hotel Sol'][2]);
    }

    /** @dataProvider themes */
    public function testTheBoundaryTableLeavesOutEveryNonConsolidatedPropertyWhenTheScope3IsExcluded(string $theme): void
    {
        $byProperty = array_column($this->rowsOf($theme, 'boundaryPropertyRows', OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(scope3Excluded: true)), null, 0);

        $this->assertSame('Fuera del cálculo', $byProperty['Hotel Franquicia'][2]);
        $this->assertSame('Fuera del cálculo', $byProperty['Hotel Arrendado'][2]);
    }

    /** @dataProvider themes */
    public function testTheCategoryMethodsTableShowsTheMethodOfEachNonConsolidatedProperty(string $theme): void
    {
        $footprintRows = $this->rowsOf($theme, 'categoryMethodRows', OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties());
        $contributionRows = $this->rowsOf($theme, 'categoryMethodRows', OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(true));

        $this->assertContains(['Alcance 3', 'Cat. 14 · Franquicias', 'Huella de la propiedad', '5.000,00', '100,00 %'], $footprintRows);
        $this->assertContains(['Alcance 3', 'Cat. 13 · Activos arrendados aguas abajo', 'Valor manual', '4.500,00', '100,00 %'], $footprintRows);
        $this->assertContains(['Alcance 3', 'Cat. 14 · Franquicias', 'Aportación del franquiciado o arrendatario', '5.000,00', '100,00 %'], $contributionRows);
    }

    /** @dataProvider themes */
    public function testTheBoundaryRowsOfAPropertyDeclareTheCategoriesThatDoNotApply(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'boundaryRows', OrganizationalCarbonFootprintReportDataMother::forProperty());
        $companyRows = $this->rowsOf($theme, 'boundaryRows', OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines());
        $notApplicable = ['Cat. 13 · Activos arrendados aguas abajo / Cat. 14 · Franquicias', 'Se informan en la huella de la empresa (propiedades arrendadas a terceros o franquiciadas)'];

        $this->assertContains(['Cat. 8 · Activos arrendados aguas arriba', 'No aplicable con el enfoque de control operacional'], $rows);
        $this->assertContains($notApplicable, $rows);
        $this->assertNotContains($notApplicable, $companyRows);
        $this->assertContains(['Cat. 8 · Activos arrendados aguas arriba', 'No aplicable con el enfoque de control operacional'], $companyRows);
    }

    /** @dataProvider themes */
    public function testTheBoundaryRowsDeclareTheExclusionOfTheScope3WithoutItsCategories(string $theme): void
    {
        $rows = $this->rowsOf($theme, 'boundaryRows', OrganizationalCarbonFootprintReportDataMother::withScope3Excluded());

        $this->assertContains(['Alcances incluidos', 'Alcance 1, Alcance 2'], $rows);
        $this->assertContains(['Alcance 3', 'Excluido · Sin datos disponibles'], $rows);
        $this->assertNotContains('Categorías del alcance 3 contempladas', array_column($rows, 0));
        $this->assertNotContains('Cat. 8 · Activos arrendados aguas arriba', array_column($rows, 0));
    }

    /** @dataProvider themes */
    public function testTheMaterialityTopicRowsShowRelevanceVerdictStateAndOverride(string $theme): void
    {
        $rows = $this->materialityInvoke($theme, 'materialityTopicRows', OrganizationalCarbonFootprintReportMaterialityAnnexMother::withOverride(), Locale::Spanish);

        $this->assertContains(['10', 'Procesamiento de los productos vendidos', '0,00 %', '50 %', 'Omitir', 'Excluida (recomendación)', '---'], $rows);
        $this->assertContains(['2', 'Bienes de capital', '33,33 %', '50 %', 'Calcular · Magnitud alta', 'Activa', '---'], $rows);
        $this->assertContains(['11', 'Uso de los productos vendidos', '16,67 %', '50 %', 'Omitir', 'Activa (anulación)', 'A. García · 14/03/2026 · Exigencia del cliente principal'], $rows);
    }

    /** @dataProvider themes */
    public function testAMaterialityCategoryWithoutAssessmentTopicIsNamedInTheLocaleOfTheReport(string $theme): void
    {
        $topic = new MaterialityAnnexTopicLine(
            topicId: null,
            number: 10,
            name: null,
            source: Source::SoldProductsProcessing,
            scorePercent: null,
            thresholdPercent: null,
            forcedByHighMagnitude: false,
            verdict: null,
            state: MaterialityCategoryState::NotEvaluated,
            exclusionReason: null,
            exclusionComment: null,
            overriddenByName: null,
            overriddenAt: null,
            overrideJustification: null,
            recommendedAt: null,
        );

        $englishRow = $this->materialityInvoke($theme, 'materialityTopicRow', $topic, Locale::English);
        $spanishRow = $this->materialityInvoke($theme, 'materialityTopicRow', $topic, Locale::Spanish);

        $this->assertSame('Processing of sold products', $englishRow[1]);
        $this->assertSame('Procesamiento de los productos vendidos', $spanishRow[1]);
        $this->assertSame('Cat. 10 · Processing of sold products', $this->materialityInvoke($theme, 'materialityTopicLabel', $topic, Locale::English));
    }

    /** @dataProvider themes */
    public function testTheMaterialityAnswerRowsListEachAnswerWithItsPointsAndTheTotal(string $theme): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete();

        $rows = $this->materialityInvoke($theme, 'materialityAnswerRows', $annex->justifications[0], Locale::Spanish);

        $this->assertCount(6, $rows);
        $this->assertSame(['Magnitud', '¿Qué magnitud tienen las emisiones de esta categoría?', 'Bajo', '2', '2'], $rows[0]);
        $this->assertSame(['Externalización', '¿Está externalizada la actividad?', 'No', '1', '1'], $rows[4]);
        $this->assertSame(['Total', 'rango 6–18', '', '', '6'], $rows[5]);
    }

    /** @dataProvider themes */
    public function testTheMaterialityJustificationTextCitesTheAnswersTheThresholdAndTheVerdict(string $theme): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete();

        $text = $this->materialityInvoke($theme, 'materialityJustificationText', $annex, $annex->justifications[0], Locale::Spanish);

        $this->assertStringContainsString('**Cat. 10 · Procesamiento de los productos vendidos — Excluida (No significativa).**', $text);
        $this->assertStringContainsString('completada el 12/03/2026 por jgarcia', $text);
        $this->assertStringContainsString('Magnitud: «¿Qué magnitud tienen las emisiones de esta categoría?», respuesta Bajo (1 × 2 = 2 puntos)', $text);
        $this->assertStringContainsString('Total 6 puntos sobre un rango de 6 a 18: relevancia 0,00 %, por debajo del umbral del 50 %.', $text);
        $this->assertStringContainsString('Veredicto: Omitir.', $text);
        $this->assertStringContainsString('con motivo «No significativa» y comentario «Análisis de materialidad: relevancia 0,00 % por debajo del umbral 50 %»', $text);
    }

    /** @dataProvider themes */
    public function testTheMaterialityJustificationTextEscapesTheTextWrittenByUsers(string $theme): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete();
        $justification = $annex->justifications[0];
        $topic = $justification->topic;
        $userTopic = new MaterialityAnnexTopicLine(
            topicId: $topic->topicId,
            number: $topic->number,
            name: $topic->name,
            source: $topic->source,
            scorePercent: $topic->scorePercent,
            thresholdPercent: $topic->thresholdPercent,
            forcedByHighMagnitude: $topic->forcedByHighMagnitude,
            verdict: $topic->verdict,
            state: MaterialityCategoryState::ExcludedOverridden,
            exclusionReason: $topic->exclusionReason,
            exclusionComment: '<img src="http://example.com/x.png"> # [z]' . "\r\n- item\n\n1.  fin",
            overriddenByName: 'A_García',
            overriddenAt: new \DateTimeImmutable('2026-03-14 11:00:00'),
            overrideJustification: '<b>x</b> **y**',
            recommendedAt: $topic->recommendedAt,
        );
        $userJustification = new MaterialityAnnexJustification($userTopic, $justification->answers, $justification->points, $justification->minPoints, $justification->maxPoints);

        $text = $this->materialityInvoke($theme, 'materialityJustificationText', $annex, $userJustification, Locale::Spanish);

        $this->assertStringContainsString('comentario «&lt;img src=&quot;http://example.com/x.png&quot;&gt; \\# \\[z\\] - item 1. fin»', $text);
        $this->assertStringContainsString('Recomendación anulada por A\\_García el 14/03/2026: «&lt;b&gt;x&lt;/b&gt; \\*\\*y\\*\\*».', $text);
        $this->assertStringNotContainsString('<b>', $text);
        $this->assertStringNotContainsString('<img', $text);
    }

    /** @dataProvider themes */
    public function testTheMaterialityAnnexStatementCoversTheDeclarativeCases(string $theme): void
    {
        $this->assertNull($this->materialityInvoke($theme, 'materialityAnnexStatement', OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), Locale::Spanish));
        $this->assertStringStartsWith('No se ha realizado un análisis de materialidad', $this->materialityInvoke($theme, 'materialityAnnexStatement', OrganizationalCarbonFootprintReportDataMother::withoutMaterialityAssessment(), Locale::Spanish));
        $this->assertStringStartsWith('No se ha realizado un análisis de materialidad', $this->materialityInvoke($theme, 'materialityAnnexStatement', OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::Spanish));
        $this->assertStringStartsWith('El alcance 3 se ha excluido en su totalidad: Sin datos disponibles. Sin datos de proveedores.', $this->materialityInvoke($theme, 'materialityAnnexStatement', OrganizationalCarbonFootprintReportDataMother::withScope3ExcludedAndMaterialityDecisions(), Locale::Spanish));
        $this->assertStringStartsWith('La evaluación fue reabierta', $this->materialityInvoke($theme, 'materialityAnnexStatement', OrganizationalCarbonFootprintReportDataMother::withReopenedMaterialityAssessment(), Locale::Spanish));
    }

    /** @dataProvider themes */
    public function testTheQuestionsChangedOrRemovedInTheMasterAreMarked(string $theme): void
    {
        $annex = OrganizationalCarbonFootprintReportMaterialityAnnexMother::withChangedQuestions();

        $questions = $this->materialityInvoke($theme, 'materialityQuestionRows', $annex, Locale::Spanish);
        $methodology = $this->materialityInvoke($theme, 'materialityMethodologyRows', $annex, Locale::Spanish);

        $this->assertContains(['Riesgo', '¿Qué riesgo supone la categoría? (modificada)', 'Bajo / Medio / Alto', '1'], $questions);
        $this->assertContains(['Externalización', '¿Está externalizada la actividad? (retirada)', 'Sí / No', '1'], $questions);
        $this->assertContains(['Versión de las preguntas', '15/01/2026 · el cuestionario ha cambiado desde entonces'], $methodology);
    }

    /** @dataProvider themes */
    public function testTheMaterialityFootnoteAppearsOnlyWithDecisions(string $theme): void
    {
        $this->assertNotNull($this->materialityInvoke($theme, 'materialityFootnote', OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), Locale::Spanish));
        $this->assertNull($this->materialityInvoke($theme, 'materialityFootnote', OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::Spanish));
    }

    private function materialityInvoke(string $theme, string $method, mixed ...$arguments): mixed
    {
        $generator = $this->generatorFor($theme);

        return (new \ReflectionMethod($generator, $method))->invoke($generator, ...$arguments);
    }

    /** @return list<list<string>> */
    private function rowsOf(string $theme, string $method, OrganizationalCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, $method))->invoke($generator, $data, Locale::Spanish);

        return $rows;
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function organizationalSourceTable(string $theme, OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source): array
    {
        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'organizationalSourceTable'))->invoke($generator, $data, $scope, $source, Locale::Spanish, false);

        return $table;
    }

    /** @return list<list<string>> */
    private function organizationalDualReportingRows(string $theme, OrganizationalCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'organizationalDualReportingRows'))->invoke($generator, $data, Locale::Spanish);

        return $rows;
    }

    /** @return list<list<string>> */
    private function eventElectricityTableRows(string $theme, EventCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} $table */
        $table = (new \ReflectionMethod($generator, 'eventElectricityTable'))->invoke($generator, $data, Locale::Spanish);

        return $table[1];
    }

    /** @return list<list<string>> */
    private function eventDualReportingRows(string $theme, EventCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<list<string>> $rows */
        $rows = (new \ReflectionMethod($generator, 'eventDualReportingRows'))->invoke($generator, $data, Locale::Spanish);

        return $rows;
    }

    /** @return list<Source> */
    private function scope3PageSources(string $theme, OrganizationalCarbonFootprintReportData $data): array
    {
        $generator = $this->generatorFor($theme);

        /** @var list<array{0: Scope, 1: Source, 2: string}> $definitions */
        $definitions = (new \ReflectionMethod($generator, 'scopeSourceDefinitions'))->invoke($generator, $data, Locale::Spanish);

        return array_values(array_map(
            static fn(array $definition): Source => $definition[1],
            array_filter($definitions, static fn(array $definition): bool => Scope::Scope3 === $definition[0]),
        ));
    }

    /** @return array{0: CarbonFootprintReportData, 1: CarbonFootprintReportTexts} */
    private function fixtureFor(string $type): array
    {
        return match ($type) {
            'event' => [EventCarbonFootprintReportDataMother::withCompensation(), CarbonFootprintReportTextsMother::forEvent()],
            'event-electricity-without-mix' => [EventCarbonFootprintReportDataMother::withElectricityWithoutLocationBasedResult(), CarbonFootprintReportTextsMother::forEvent()],
            'property' => [OrganizationalCarbonFootprintReportDataMother::forProperty(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'company-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(CarbonFootprintReportSubjectType::Company), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'property-scope-3' => [OrganizationalCarbonFootprintReportDataMother::withScope3(), CarbonFootprintReportTextsMother::forOrganization()],
            'company-scope-3' => [OrganizationalCarbonFootprintReportDataMother::withScope3(CarbonFootprintReportSubjectType::Company), CarbonFootprintReportTextsMother::forOrganization()],
            'company-non-consolidated' => [OrganizationalCarbonFootprintReportDataMother::withNonConsolidatedProperties(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-scope-3-excluded' => [OrganizationalCarbonFootprintReportDataMother::withScope3Excluded(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-precalculated' => [OrganizationalCarbonFootprintReportDataMother::precalculated(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-long-mitigation' => [OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(targetYear: 2035), CarbonFootprintReportTextsMother::forOrganizationWithMitigationPlan()],
            'property-mixed-methods' => [OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), CarbonFootprintReportTextsMother::forOrganization()],
            'company-boundaries' => [OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-materiality' => [OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), CarbonFootprintReportTextsMother::forOrganization()],
            'company-materiality-override' => [OrganizationalCarbonFootprintReportDataMother::withMaterialityOverride(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-scope-3-excluded-materiality' => [OrganizationalCarbonFootprintReportDataMother::withScope3ExcludedAndMaterialityDecisions(), CarbonFootprintReportTextsMother::forOrganization()],
            'property-materiality-reopened' => [OrganizationalCarbonFootprintReportDataMother::withReopenedMaterialityAssessment(), CarbonFootprintReportTextsMother::forOrganization()],
            default => [OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(), CarbonFootprintReportTextsMother::forOrganization()],
        };
    }

    private function generatorFor(string $theme): CarbonFootprintReportPdfGenerator
    {
        return 'classic' === $theme
            ? new ClassicTcpdfCarbonFootprintReportPdfGenerator($this->translator())
            : new TcpdfCarbonFootprintReportPdfGenerator($this->translator(), ThemeRegistryMother::withThemes([new GreenhostTheme()]));
    }

    private function translator(): Translator
    {
        $translator = new Translator(Locale::Spanish->value);
        $translator->addLoader('xlf', new XliffFileLoader());

        foreach (Locale::cases() as $locale) {
            foreach (['messages', 'scope', 'source', 'activityType', 'phase', 'exclusionReason', 'operatingRegime', 'eventSectorType', 'eventTypologyType', 'eventSizeType', 'materialityVerdictType', 'materialityCategoryStateType', 'materialityCriterionType', 'materialityAnswerValueType', 'materialityAnswerScaleType'] as $domain) {
                $catalogue = sprintf('%s/translations/%s+intl-icu.%s.xlf', $this->rootDirectory(), $domain, $locale->value);

                if (is_file($catalogue)) {
                    $translator->addResource('xlf', $catalogue, $locale->value, $domain . '+intl-icu');
                }
            }
        }

        return $translator;
    }

    private function previewPath(string $theme, string $name): string
    {
        $directory = $this->rootDirectory() . '/' . self::PREVIEW_DIRECTORY . '/' . $theme;

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            $this->fail(sprintf('Could not create the preview directory %s', $directory));
        }

        return $directory . '/' . $name . '.pdf';
    }

    private function rootDirectory(): string
    {
        return dirname(__DIR__, 5);
    }
}
