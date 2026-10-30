<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Infrastructure\AI\Prompt\GenerateOrganizationalReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiThinkingLevel;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GenerateOrganizationalReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaHasTheSevenRequiredSections(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $schema = $prompt->responseSchema();

        $this->assertSame('OBJECT', $schema['type']);
        $this->assertSame(['purpose', 'regulatoryContext', 'methodology', 'emissionFactorSources', 'uncertainty', 'sustainableDevelopmentGoals', 'conclusion'], $schema['required']);
        foreach ($schema['required'] as $property) {
            $this->assertSame('STRING', $schema['properties'][$property]['type']);
        }
    }

    public function testModelTierIsMedium(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $this->assertSame(AiModelTier::MEDIUM, $prompt->modelTier());
        $this->assertSame(AiThinkingLevel::MEDIUM, $prompt->thinkingLevel());
    }

    public function testTextIncludesTheSubjectDataTotalsAndExclusions(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('Name: Hotel Sol', $text);
        $this->assertStringContainsString('Calculation year: 2025', $text);
        $this->assertStringContainsString('- scope_1: 1430', $text);
        $this->assertStringContainsString('- scope_2: 2500', $text);
        $this->assertStringContainsString('Location-based total emissions:', $text);
        $this->assertStringContainsString('scope_1 | refrigerant_leaks | not_applicable | Sin equipos de refrigeración', $text);
        $this->assertStringContainsString('Write all output in English', $text);
        $this->assertStringNotContainsString('Emissions by property', $text);
    }

    public function testWithScope3TheTextIncludesItsEmissionsAndTheThreeBoundaries(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('- scope_3: 5490', $text);
        $this->assertStringContainsString('- scope_3 | fuel_and_energy_related: 590', $text);
        $this->assertStringContainsString('the scope 1, scope 2 and scope 3 boundaries', $text);
    }

    public function testWithScope3ExcludedTheTextDeclaresTheExclusionAndItsReason(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3Excluded(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('the scope 1 and scope 2 boundaries (scope 3 excluded: no_data_available)', $text);
        $this->assertStringContainsString('- scope_3 | (whole scope) | no_data_available | Sin datos de proveedores', $text);
    }

    public function testAPrecalculatedFootprintDeclaresTheAlreadyCalculatedResults(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::precalculated(), Locale::English);

        $this->assertStringContainsString('already calculated results', $prompt->text());
    }

    public function testAFootprintNotPrecalculatedDoesNotDeclareAlreadyCalculatedResults(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::English);

        $this->assertStringNotContainsString('already calculated results', $prompt->text());
    }

    public function testTextIncludesTheBreakdownByPropertyForACompany(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(), Locale::Spanish);

        $text = $prompt->text();

        $this->assertStringContainsString('Emissions by property', $text);
        $this->assertStringContainsString('- Hotel Sol: 2680', $text);
        $this->assertStringContainsString('- Sin asignar a propiedad: 1250', $text);
    }

    public function testWithAMitigationPlanTheTextListsTheMeasuresAndAsksForTheSection(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMitigationPlan(), Locale::Spanish);

        $text = $prompt->text();
        $schema = $prompt->responseSchema();

        $this->assertStringContainsString('Mitigation plan', $text);
        $this->assertStringContainsString('- Measure: Caldera de condensación | base year 2025 | target year 2030', $text);
        $this->assertStringContainsString('- Measure: Paneles fotovoltaicos', $text);
        $this->assertStringContainsString('8. mitigationPlan:', $text);
        $this->assertStringContainsString('list ALL the measures', $text);
        $this->assertStringContainsString('"mitigationPlan": "string"', $text);
        $this->assertSame('STRING', $schema['properties']['mitigationPlan']['type']);
        $this->assertNotContains('mitigationPlan', $schema['required']);
    }

    public function testTextDeclaresTheConsolidationApproachTheGwpAndAsksForTheDossierSections(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('Consolidation approach: operational control', $text);
        $this->assertStringContainsString('Operating regime: owned', $text);
        $this->assertStringContainsString('CH4 27.9, N2O 273', $text);
        $this->assertStringContainsString('3. methodology:', $text);
        $this->assertStringContainsString('4. emissionFactorSources:', $text);
        $this->assertStringContainsString('5. uncertainty:', $text);
        $this->assertStringContainsString('"uncertainty": "string"', $text);
        $this->assertStringContainsString('Employee survey: no survey data', $text);
    }

    public function testTextIncludesTheMethodsByCategoryTheFactorSourcesAndTheEmployeeSurvey(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('- scope_3 | purchased_goods_and_services | 1 | spend_based | 3000.00 | low', $text);
        $this->assertStringContainsString('- scope_3 | purchased_goods_and_services | 1 | supplier_specific | 1200.00 | high', $text);
        $this->assertStringContainsString('- scope_3 | employee_commuting | 7 | employee_survey_extrapolation | 900.00 | medium', $text);
        $this->assertStringContainsString('- scope_3 | fuel_and_energy_related | 3 | derived_well_to_tank | 590.00 | medium', $text);
        $this->assertStringContainsString('- MITECO | 2025 | 2 | 1430.00', $text);
        $this->assertStringContainsString('- DEFRA | 2024 | 1 | 900.00', $text);
        $this->assertStringContainsString('Employee survey: 40 employees, 25 answers, confidence level 95 %, margin of error 6.50 %', $text);
    }

    public function testTextIncludesTheOrganizationalBoundaryOfACompany(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(), Locale::Spanish);

        $text = $prompt->text();

        $this->assertStringContainsString('- Hotel Franquicia | franchised | scope 3 category 14', $text);
        $this->assertStringContainsString('- Hotel Sol | owned | consolidated in scopes 1 and 2', $text);
        $this->assertStringContainsString('- scope_3 | downstream_leased_assets | 13 | manual_value | 4500.00 | low', $text);
        $this->assertStringContainsString('- operator_footprint | n/a | 1 | 5000.00', $text);
        $this->assertStringContainsString('- manual_value | n/a | 1 | 4500.00', $text);
        $this->assertStringNotContainsString('are not applicable to a property', $text);
    }

    public function testTheFactorSourcesIncludeTheDerivedEmissions(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMixedMethods(), Locale::English);

        $this->assertStringContainsString('- derived_well_to_tank | n/a | 2 | 590.00', $prompt->text());
    }

    public function testAPropertyWhoseCategoryIsExcludedIsOutsideTheCalculation(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(Source::Franchises), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('- Hotel Franquicia | franchised | outside the calculation (excluded)', $text);
        $this->assertStringContainsString('- Hotel Arrendado | leased_out | scope 3 category 13', $text);
    }

    public function testWithScope3ExcludedEveryNonConsolidatedPropertyIsOutsideTheCalculation(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forCompanyWithBoundaryLines(scope3Excluded: true), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('- Hotel Franquicia | franchised | outside the calculation (excluded)', $text);
        $this->assertStringContainsString('- Hotel Arrendado | leased_out | outside the calculation (excluded)', $text);
        $this->assertStringNotContainsString('scope 3 category', $text);
    }

    public function testAPropertyReportDeclaresThatTheCompanyOnlyCategoriesDoNotApply(): void
    {
        $text = (new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::English))->text();
        $excludedText = (new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3Excluded(), Locale::English))->text();

        $this->assertStringContainsString('Scope 3 categories 13 and 14 are not applicable to a property: they are reported in the footprint of the company (leased-out or franchised properties)', $text);
        $this->assertStringNotContainsString('are not applicable to a property', $excludedText);
    }

    public function testWithoutAMitigationPlanTheSectionIsNotRequested(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $text = $prompt->text();

        $this->assertStringNotContainsString('Mitigation plan', $text);
        $this->assertStringNotContainsString('mitigationPlan', $text);
        $this->assertArrayNotHasKey('mitigationPlan', $prompt->responseSchema()['properties']);
    }
}
