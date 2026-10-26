<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateEventReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\AiModelTier;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GenerateReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaHasFourRequiredStringProperties(): void
    {
        $prompt = new GenerateEventReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $schema = $prompt->responseSchema();

        $this->assertSame('OBJECT', $schema['type']);
        $this->assertSame(
            ['executiveSummary', 'methodology', 'emissionFactorSources', 'analysisAndRecommendations'],
            $schema['required'],
        );
        foreach ($schema['required'] as $property) {
            $this->assertSame('STRING', $schema['properties'][$property]['type']);
        }
    }

    public function testModelTierIsMedium(): void
    {
        $prompt = new GenerateEventReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame(AiModelTier::MEDIUM, $prompt->modelTier());
    }

    public function testTextIncludesTheTargetLanguage(): void
    {
        $prompt = new GenerateEventReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::English);

        $this->assertStringContainsString('Write all output in English', $prompt->text());
    }

    public function testTextIncludesEventData(): void
    {
        $data = EventCarbonFootprintReportDataMother::random();
        $prompt = new GenerateEventReportTextsPrompt($data, Locale::Spanish);

        $this->assertStringContainsString($data->eventName, $prompt->text());
    }

    public function testTextIncludesTheLocationBasedFigures(): void
    {
        $prompt = new GenerateEventReportTextsPrompt(EventCarbonFootprintReportDataMother::withCompensation(), Locale::Spanish);

        $this->assertStringContainsString('Location-based total emissions: 8340 kg CO2eq', $prompt->text());
    }

    public function testTextIncludesTheExclusionsBlockWhenThereAreExclusionLines(): void
    {
        $data = EventCarbonFootprintReportDataMother::random([
            new EventCarbonFootprintReportExclusionLine(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'no invoices'),
        ]);
        $prompt = new GenerateEventReportTextsPrompt($data, Locale::Spanish);

        $text = $prompt->text();
        $this->assertStringContainsString('Excluded emission sources', $text);
        $this->assertStringContainsString('assembly | waste_management | no_data_available | no invoices', $text);
        $this->assertStringContainsString('limits of the scope', $text);
    }

    public function testTextOmitsTheExclusionsBlockWhenThereAreNoExclusionLines(): void
    {
        $prompt = new GenerateEventReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertStringNotContainsString('Excluded emission sources', $prompt->text());
    }
}
