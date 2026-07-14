<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\TranslateReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\GeminiEventCarbonFootprintReportTextsGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GeminiEventCarbonFootprintReportTextsGeneratorTest extends TestCase
{
    public function testMapsResponseIntoTexts(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'executiveSummary' => 'Summary line 1\nSummary line 2',
            'methodology' => 'Methodology text',
            'emissionFactorSources' => 'Sources text',
            'analysisAndRecommendations' => 'Analysis text',
        ]);

        $sut = new GeminiEventCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame("Summary line 1\nSummary line 2", $texts->executiveSummary);
        $this->assertSame('Methodology text', $texts->methodology);
        $this->assertSame('Sources text', $texts->emissionFactorSources);
        $this->assertSame('Analysis text', $texts->analysisAndRecommendations);
        $this->assertInstanceOf(GenerateReportTextsPrompt::class, $client->lastPrompt());
    }

    public function testTranslateSendsTranslatePromptAndMapsResponse(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'executiveSummary' => 'Translated summary',
            'methodology' => 'Translated methodology',
            'emissionFactorSources' => 'Translated sources',
            'analysisAndRecommendations' => 'Translated analysis',
        ]);

        $sut = new GeminiEventCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->translate(
            new EventCarbonFootprintReportTexts('summary-es', 'methodology-es', 'sources-es', 'analysis-es'),
            Locale::Spanish,
            Locale::English,
        );

        $this->assertSame('Translated summary', $texts->executiveSummary);
        $this->assertSame('Translated methodology', $texts->methodology);
        $this->assertSame('Translated sources', $texts->emissionFactorSources);
        $this->assertSame('Translated analysis', $texts->analysisAndRecommendations);
        $this->assertInstanceOf(TranslateReportTextsPrompt::class, $client->lastPrompt());
    }

    public function testThrowsWhenResponseKeepsReturningCorruptedCharacters(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'executiveSummary' => "El car\x01cter del a\x06o",
            'methodology' => 'Methodology text',
            'emissionFactorSources' => 'Sources text',
            'analysisAndRecommendations' => 'Analysis text',
        ]);

        $sut = new GeminiEventCarbonFootprintReportTextsGenerator($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('corrupted characters');

        $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);
    }

    public function testMissingFieldsBecomeEmptyStrings(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse(['executiveSummary' => 'Only summary']);

        $sut = new GeminiEventCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame('Only summary', $texts->executiveSummary);
        $this->assertSame('', $texts->methodology);
        $this->assertSame('', $texts->emissionFactorSources);
        $this->assertSame('', $texts->analysisAndRecommendations);
    }
}
