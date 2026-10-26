<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Infrastructure\AI\Prompt\TranslateReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\AiModelTier;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use PHPUnit\Framework\TestCase;

class TranslateReportTextsPromptTest extends TestCase
{
    public function testResponseSchemaFollowsThePresentSections(): void
    {
        $prompt = new TranslateReportTextsPrompt(CarbonFootprintReportTextsMother::forOrganization(), Locale::Spanish, Locale::English);

        $schema = $prompt->responseSchema();

        $this->assertSame('OBJECT', $schema['type']);
        $this->assertSame(['purpose', 'regulatoryContext', 'sustainableDevelopmentGoals', 'conclusion'], $schema['required']);
        foreach ($schema['required'] as $property) {
            $this->assertSame('STRING', $schema['properties'][$property]['type']);
        }
    }

    public function testEventTextsWithCompensationHaveFiveSections(): void
    {
        $prompt = new TranslateReportTextsPrompt(CarbonFootprintReportTextsMother::forEvent(), Locale::Spanish, Locale::English);

        $this->assertCount(5, $prompt->responseSchema()['required']);
        $this->assertStringContainsString('Translate the following 5 Markdown sections', $prompt->text());
    }

    public function testModelTierIsLow(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

        $this->assertSame(AiModelTier::LOW, $prompt->modelTier());
    }

    public function testTextIncludesSourceAndTargetLanguages(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::French);

        $this->assertStringContainsString('from Spanish to French', $prompt->text());
    }

    public function testTextDelimitsSectionsWithTagsAndKeepsTheMarkdownInstruction(): void
    {
        $prompt = new TranslateReportTextsPrompt($this->texts(), Locale::Spanish, Locale::English);

        $text = $prompt->text();

        $this->assertStringContainsString("<executiveSummary>\nsummary-es\n</executiveSummary>", $text);
        $this->assertStringContainsString("<methodology>\nmethodology-es\n</methodology>", $text);
        $this->assertStringContainsString('Preserve the Markdown structure exactly', $text);
        $this->assertStringContainsString('a ### heading must stay ###', $text);
        $this->assertStringNotContainsString('## executiveSummary', $text);
    }

    private function texts(): CarbonFootprintReportTexts
    {
        return new CarbonFootprintReportTexts([
            'executiveSummary' => 'summary-es',
            'methodology' => 'methodology-es',
            'emissionFactorSources' => 'sources-es',
            'analysisAndRecommendations' => 'analysis-es',
        ]);
    }
}
