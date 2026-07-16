<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GenerateReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaHasFourRequiredStringProperties(): void
    {
        $prompt = new GenerateReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

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
        $prompt = new GenerateReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame(GeminiModelTier::MEDIUM, $prompt->modelTier());
    }

    public function testTextIncludesTheTargetLanguage(): void
    {
        $prompt = new GenerateReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::English);

        $this->assertStringContainsString('Write all output in English', $prompt->text());
    }

    public function testTextIncludesEventData(): void
    {
        $data = EventCarbonFootprintReportDataMother::random();
        $prompt = new GenerateReportTextsPrompt($data, Locale::Spanish);

        $this->assertStringContainsString($data->eventName, $prompt->text());
    }

    public function testTextIncludesTheExclusionsBlockWhenThereAreExclusionLines(): void
    {
        $data = EventCarbonFootprintReportDataMother::random([
            new EventCarbonFootprintReportExclusionLine(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'no invoices'),
        ]);
        $prompt = new GenerateReportTextsPrompt($data, Locale::Spanish);

        $text = $prompt->text();
        $this->assertStringContainsString('Excluded emission sources', $text);
        $this->assertStringContainsString('assembly | waste_management | no_data_available | no invoices', $text);
        $this->assertStringContainsString('limits of the scope', $text);
    }

    public function testTextOmitsTheExclusionsBlockWhenThereAreNoExclusionLines(): void
    {
        $prompt = new GenerateReportTextsPrompt(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertStringNotContainsString('Excluded emission sources', $prompt->text());
    }
}
