<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateOrganizationalReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiThinkingLevel;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GenerateOrganizationalReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaHasTheFourRequiredSections(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $schema = $prompt->responseSchema();

        $this->assertSame('OBJECT', $schema['type']);
        $this->assertSame(['purpose', 'regulatoryContext', 'sustainableDevelopmentGoals', 'conclusion'], $schema['required']);
        foreach ($schema['required'] as $property) {
            $this->assertSame('STRING', $schema['properties'][$property]['type']);
        }
    }

    public function testModelTierIsMedium(): void
    {
        $prompt = new GenerateOrganizationalReportTextsPrompt(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $this->assertSame(GeminiModelTier::MEDIUM, $prompt->modelTier());
        $this->assertSame(GeminiThinkingLevel::MEDIUM, $prompt->thinkingLevel());
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
        $this->assertStringContainsString('5. mitigationPlan:', $text);
        $this->assertStringContainsString('list ALL the measures', $text);
        $this->assertStringContainsString('"mitigationPlan": "string"', $text);
        $this->assertSame('STRING', $schema['properties']['mitigationPlan']['type']);
        $this->assertNotContains('mitigationPlan', $schema['required']);
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
