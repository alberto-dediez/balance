<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\CarbonFootprint\Infrastructure\AI\Gemini\TranslateReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use PHPUnit\Framework\TestCase;

class TranslateReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaHasFourRequiredStringProperties(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

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

    public function testModelTierIsLow(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

        $this->assertSame(GeminiModelTier::LOW, $prompt->modelTier());
    }

    public function testTextIncludesSourceAndTargetLanguages(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::French);

        $this->assertStringContainsString('from Spanish to French', $prompt->text());
    }

    public function testTextIncludesTheSectionsAndMarkdownPreservationInstruction(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString('summary-es', $text);
        $this->assertStringContainsString('methodology-es', $text);
        $this->assertStringContainsString('sources-es', $text);
        $this->assertStringContainsString('analysis-es', $text);
        $this->assertStringContainsString('Preserve the Markdown structure exactly', $text);
        $this->assertStringContainsString('a ### heading must stay ###', $text);
    }

    public function testTextDelimitsSectionsWithTagsInsteadOfMarkdownHeadings(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString("<executiveSummary>\nsummary-es\n</executiveSummary>", $text);
        $this->assertStringContainsString("<methodology>\nmethodology-es\n</methodology>", $text);
        $this->assertStringContainsString("<emissionFactorSources>\nsources-es\n</emissionFactorSources>", $text);
        $this->assertStringContainsString("<analysisAndRecommendations>\nanalysis-es\n</analysisAndRecommendations>", $text);
        $this->assertStringNotContainsString('## executiveSummary', $text);
    }

    private function texts(): EventCarbonFootprintReportTexts
    {
        return new EventCarbonFootprintReportTexts(
            executiveSummary: 'summary-es',
            methodology: 'methodology-es',
            emissionFactorSources: 'sources-es',
            analysisAndRecommendations: 'analysis-es',
        );
    }
}
