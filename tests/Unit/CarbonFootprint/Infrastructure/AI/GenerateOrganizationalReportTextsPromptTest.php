<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
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

    public function testTheGasesOfTheTotalAreSentInKilograms(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::forProperty();
        $data = new OrganizationalCarbonFootprintReportData(...array_merge(get_object_vars($data), ['totalEmissions' => new Emissions('3930', '3900', '5000', '2000')]));

        $text = new GenerateOrganizationalReportTextsPrompt($data, Locale::English)->text();

        $this->assertStringContainsString('Total emissions: 3930 kg CO2eq, 3900 kg CO2, 5 kg CH4, 2 kg N2O', $text);
    }

    public function testAPrecalculatedFootprintDoesNotSendTheLocationBasedTotal(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::precalculated(), Locale::English)->text();

        $this->assertStringNotContainsString('Location-based total emissions:', $text);
    }

    public function testTheCo2eqOnlyActivitiesAreDeclared(): void
    {
        $this->assertStringContainsString('accounted only in CO2eq', new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::precalculated(), Locale::English)->text());
        $this->assertStringNotContainsString('accounted only in CO2eq', new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::English)->text());
    }

    public function testTheBaseYearIsSentWithItsVariationAndThreshold(): void
    {
        $data = OrganizationalCarbonFootprintReportDataMother::withBaseYear(OrganizationalCarbonFootprintReportDataMother::withScope3(), OrganizationalCarbonFootprintReportDataMother::baseYear(baseYearTotalKgCo2eq: '8000'));

        $text = new GenerateOrganizationalReportTextsPrompt($data, Locale::English)->text();

        $this->assertStringContainsString('Base year: 2023 | base-year total 8000 kg CO2eq | recalculation threshold 5 % | variation +17.75 % | exceeds: yes | justification: Primer año con datos completos', $text);
    }

    public function testAFootprintWithoutBaseYearDeclaresIt(): void
    {
        $this->assertStringContainsString('Base year: not declared', new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::English)->text());
    }

    public function testTheProviderContributionsAndTheSupplierDataShareAreSent(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withProviderLines(), Locale::English)->text();

        $this->assertStringContainsString('- Lavandería Norte | n/a | purchased_goods_and_services | manual | answered | 2026-02-10 | 1098', $text);
        $this->assertStringContainsString('- Catering Este | n/a | purchased_goods_and_services | requested | ignored | n/a | 0', $text);
        $this->assertStringContainsString('Supplier data share of scope 3: 20.00 %', $text);
    }

    public function testWithoutProviderContributionsTheTextDeclaresIt(): void
    {
        $this->assertStringContainsString('Provider contributions: none registered', new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::English)->text());
        $this->assertStringNotContainsString('Provider contributions', new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3Excluded(), Locale::English)->text());
    }

    public function testAnAssessmentCompletedBeforeTheVerdictsIsDeclared(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessmentCompletedWithoutVerdicts(), Locale::English)->text();

        $this->assertStringContainsString('completed before the automatic verdicts were activated', $text);
        $this->assertStringNotContainsString('No materiality assessment was performed for this footprint', $text);
    }

    public function testAnAssessmentCompletedBeforeTheVerdictsDoesNotAskToDenyTheAssessmentInTheRegulatoryContext(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessmentCompletedWithoutVerdicts(), Locale::English)->text();

        $this->assertStringNotContainsString('no materiality assessment was performed', $text);
        $this->assertStringContainsString('state that the materiality assessment was completed before the automatic verdicts were activated and that scope 3 exclusions rely on the reason and comment declared for each excluded source; never add reasons that are not listed.', $text);
    }

    public function testAnAssessmentCompletedWithoutScope3TopicsIsDeclared(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessmentCompletedWithoutScope3Topics(), Locale::English)->text();

        $this->assertStringContainsString('The materiality assessment was completed without scope 3 categories', $text);
        $this->assertStringContainsString('state that the materiality assessment was completed without scope 3 categories and that scope 3 exclusions rely on the reason and comment declared for each excluded source; never add reasons that are not listed.', $text);
        $this->assertStringNotContainsString('completed before the automatic verdicts were activated', $text);
        $this->assertStringNotContainsString('No materiality assessment was performed for this footprint', $text);
        $this->assertStringNotContainsString('no materiality assessment was performed', $text);
        $this->assertStringNotContainsString('scope 3 exclusions follow the materiality assessment', $text);
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
        $this->assertStringContainsString('- scope_3 | upstream_transport | 4 | distance_based | 2000.00 | medium', $text);
        $this->assertStringContainsString('- scope_3 | operations_waste | 5 | waste_type_specific | 500.00 | medium', $text);
        $this->assertStringContainsString('distance-based', $text);
        $this->assertStringContainsString('- MITECO | 2025 | 2 | 1430.00', $text);
        $this->assertStringContainsString('- DEFRA | 2024 | 1 | 900.00', $text);
        $this->assertStringContainsString('Employee survey: 40 employees, 25 answers, participation rate 62.50 %, confidence level 95 %, margin of error 6.50 %', $text);
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

        $this->assertStringContainsString('- derived_well_to_tank (DEFRA) | 2024, 2025 | 2 | 590.00', $prompt->text());
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

    public function testWithAMaterialityAssessmentTheTextListsTheSystemVerdictsAndForbidsOwnJustifications(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('Scope 3 materiality assessment (system verdicts; cite them and never produce your own verdicts; only the materialityAnalysis section may explain them, using exclusively the answers listed):', $text);
        $this->assertStringContainsString('Threshold: 50 % | high-magnitude rule: on | completed at: 2026-03-12 | completed by: jgarcia | sector: Hostelería | question set version: 2026-01-15', $text);
        $this->assertStringContainsString('- Cat. 10 Procesamiento de los productos vendidos | relevance 0.00 % | threshold 50 % | omit | forced: no | excluded_recommended | override: none', $text);
        $this->assertStringContainsString('- Cat. 2 Bienes de capital | relevance 33.33 % | threshold 50 % | calculate | forced: yes | active | override: none', $text);
        $this->assertStringContainsString('cite its threshold and the verdicts above verbatim; never add reasons that are not listed', $text);
        $this->assertStringContainsString('describe the materiality assessment only with the criteria, scale, threshold and rule listed above', $text);
        $this->assertStringContainsString('mention manual overrides and pending reviews of the materiality assessment exactly as listed', $text);
        $this->assertStringContainsString('| criteria: magnitude, influence, risk, stakeholder_interest, outsourcing | scale: low/medium/high = 1/2/3; no/yes = 1/3; normalized relevance 0–100 %', $text);
        $this->assertStringNotContainsString('No materiality assessment was performed', $text);
        $this->assertStringNotContainsString('state that no materiality assessment was performed', $text);
    }

    public function testAMaterialityOverrideIsListedWithItsAuthorDateAndJustification(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityOverride(), Locale::English);

        $this->assertStringContainsString('| active_overridden | override: A. García, 2026-03-14, "Exigencia del cliente principal"', $prompt->text());
    }

    public function testWithoutMaterialityAssessmentTheTextDeclaresItAndForbidsInventingIt(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withoutMaterialityAssessment(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('No materiality assessment was performed for this footprint; scope 3 exclusions rely only on the declared reason and comment. Do not describe or invent a materiality analysis.', $text);
        $this->assertStringNotContainsString('materialityAnalysis', $text);
        $this->assertArrayNotHasKey('materialityAnalysis', $prompt->responseSchema()['properties']);
        $this->assertStringNotContainsString('system verdicts', $text);
        $this->assertStringContainsString('state that no materiality assessment was performed and exclusions rely on the declared reasons; never add reasons that are not listed.', $text);
        $this->assertStringNotContainsString('follow the materiality assessment', $text);
        $this->assertStringNotContainsString('describe the materiality assessment only with', $text);
        $this->assertStringNotContainsString('pending reviews of the materiality assessment', $text);
    }

    public function testAReopenedMaterialityAssessmentIsDeclaredWithTheAppliedVerdicts(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withReopenedMaterialityAssessment(), Locale::English)->text();

        $this->assertStringContainsString('The materiality assessment was reopened and has not been completed again', $text);
        $this->assertStringContainsString('- Cat. 10 Procesamiento de los productos vendidos | relevance 0.00 %', $text);
    }

    public function testWithScope3ExcludedAndMaterialityDecisionsTheRecommendationsAreNotApplied(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3ExcludedAndMaterialityDecisions(), Locale::English);
        $text = $prompt->text();

        $this->assertStringContainsString('Scope 3 was excluded in full: no_data_available. Sin datos de proveedores. The recommendations of the materiality assessment are not applied.', $text);
        $this->assertStringNotContainsString('materialityAnalysis', $text);
        $this->assertArrayNotHasKey('materialityAnalysis', $prompt->responseSchema()['properties']);
        $this->assertStringContainsString('| stage_excluded |', $text);
        $this->assertStringContainsString('state that scope 3 was excluded in full with the declared reason and that the materiality recommendations are not applied; never add reasons that are not listed.', $text);
        $this->assertStringNotContainsString('follow the materiality assessment', $text);
        $this->assertStringNotContainsString('state that no materiality assessment was performed', $text);
        $this->assertStringNotContainsString('describe the materiality assessment only with', $text);
        $this->assertStringNotContainsString('pending reviews of the materiality assessment', $text);
    }

    public function testACategoryWithoutAssessmentTopicIsListedWithItsSource(): void
    {
        $text = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityDecisionsWithoutAssessment(), Locale::English)->text();

        $this->assertStringContainsString('- Cat. 10 sold_products_processing | relevance 0.00 % | threshold 50 % | no verdict | forced: no | excluded_recommended | override: none', $text);
    }

    public function testTheMaterialityAssessmentOnlyAddsTheOptionalSection(): void
    {
        $withMateriality = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), Locale::English)->responseSchema();
        $without = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withScope3(), Locale::English)->responseSchema();

        $this->assertSame($without['required'], $withMateriality['required']);
        $this->assertSame([...$without['properties'], 'materialityAnalysis' => ['type' => 'STRING']], $withMateriality['properties']);
    }

    public function testWithExcludedCategoriesTheTextListsTheirAnswersAndAsksForTheMaterialityAnalysis(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessment(), Locale::English);

        $text = $prompt->text();
        $schema = $prompt->responseSchema();

        $this->assertStringContainsString("- Cat. 10 Procesamiento de los productos vendidos | relevance 0.00 % | threshold 50 % | omit | forced: no | excluded_recommended | override: none\n  Answer: magnitude | \"¿Qué magnitud tienen las emisiones de esta categoría?\" | level_1 | 1 × 2 = 2", $text);
        $this->assertStringContainsString('  Answer: outsourcing | "¿Está externalizada la actividad?" | yes_no_1 | 1 × 1 = 1', $text);
        $this->assertStringContainsString('  Total: 6 points in range 6–18', $text);
        $this->assertStringNotContainsString("| calculate | forced: yes | active | override: none\n  Answer:", $text);
        $this->assertStringContainsString('Write eight sections:', $text);
        $this->assertStringContainsString('8. materialityAnalysis: explain the scope 3 materiality assessment for a verifier', $text);
        $this->assertStringContainsString('Do not add facts, answers or figures that are not listed; do not change or question any verdict.', $text);
        $this->assertStringContainsString('connect the answers listed for that category', $text);
        $this->assertStringNotContainsString('do not describe, assume or infer any answer', $text);
        $this->assertStringContainsString('"conclusion": "string", "materialityAnalysis": "string"}', $text);
        $this->assertSame('STRING', $schema['properties']['materialityAnalysis']['type']);
        $this->assertNotContains('materialityAnalysis', $schema['required']);
    }

    public function testWithAMitigationPlanAndExcludedCategoriesBothOptionalSectionsAreRequested(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMitigationPlanAndMaterialityAssessment(), Locale::English);

        $text = $prompt->text();
        $schema = $prompt->responseSchema();

        $this->assertStringContainsString('Write nine sections:', $text);
        $this->assertStringContainsString('8. mitigationPlan:', $text);
        $this->assertStringContainsString('9. materialityAnalysis:', $text);
        $this->assertStringContainsString('"mitigationPlan": "string", "materialityAnalysis": "string"}', $text);
        $this->assertSame('STRING', $schema['properties']['mitigationPlan']['type']);
        $this->assertSame('STRING', $schema['properties']['materialityAnalysis']['type']);
        $this->assertNotContains('mitigationPlan', $schema['required']);
        $this->assertNotContains('materialityAnalysis', $schema['required']);
    }

    public function testWithoutExcludedCategoriesTheMaterialityAnalysisIsNotRequested(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withMaterialityAssessmentWithoutExclusions(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('Scope 3 materiality assessment (system verdicts; cite them, do not produce your own justification):', $text);
        $this->assertStringContainsString('Write seven sections:', $text);
        $this->assertStringNotContainsString('materialityAnalysis', $text);
        $this->assertStringNotContainsString('Answer:', $text);
        $this->assertArrayNotHasKey('materialityAnalysis', $prompt->responseSchema()['properties']);
    }

    public function testAReopenedAssessmentWithExcludedCategoriesAsksForTheAnalysisWithoutAnswers(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::withReopenedMaterialityAssessment(), Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('the current answers are not available', $text);
        $this->assertStringContainsString('8. materialityAnalysis:', $text);
        $this->assertStringContainsString('The answers are not available: do not describe, assume or infer any answer.', $text);
        $this->assertStringContainsString('explain the category only with its relevance, threshold, verdict and manual override as listed', $text);
        $this->assertStringNotContainsString('connect the answers listed', $text);
        $this->assertStringNotContainsString('Answer:', $text);
        $this->assertStringNotContainsString('Total:', $text);
        $this->assertArrayHasKey('materialityAnalysis', $prompt->responseSchema()['properties']);
    }
}
