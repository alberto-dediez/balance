<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateEventReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateOrganizationalReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\TranslateReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\GeminiCarbonFootprintReportTextsGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\InMemoryGeminiClient;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;

class GeminiCarbonFootprintReportTextsGeneratorTest extends TestCase
{
    public function testMapsAnEventResponseIntoTexts(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'executiveSummary' => 'Summary line 1\nSummary line 2',
            'methodology' => 'Methodology text',
            'emissionFactorSources' => 'Sources text',
            'analysisAndRecommendations' => 'Analysis text',
        ]);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame([
            'executiveSummary' => "Summary line 1\nSummary line 2",
            'methodology' => 'Methodology text',
            'emissionFactorSources' => 'Sources text',
            'analysisAndRecommendations' => 'Analysis text',
        ], $texts->sections);
        $this->assertInstanceOf(GenerateEventReportTextsPrompt::class, $client->lastPrompt());
    }

    public function testMapsAnOrganizationalResponseIntoTexts(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'purpose' => 'Purpose text',
            'regulatoryContext' => 'Context text',
            'sustainableDevelopmentGoals' => 'SDG text',
            'conclusion' => 'Conclusion text',
        ]);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(OrganizationalCarbonFootprintReportDataMother::forProperty(), Locale::Spanish);

        $this->assertSame(['purpose', 'regulatoryContext', 'sustainableDevelopmentGoals', 'conclusion'], array_keys($texts->sections));
        $this->assertSame('Conclusion text', $texts->sections['conclusion']);
        $this->assertInstanceOf(GenerateOrganizationalReportTextsPrompt::class, $client->lastPrompt());
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

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->translate(
            new CarbonFootprintReportTexts(['executiveSummary' => 'summary-es', 'methodology' => 'methodology-es', 'emissionFactorSources' => 'sources-es', 'analysisAndRecommendations' => 'analysis-es']),
            Locale::Spanish,
            Locale::English,
        );

        $this->assertSame('Translated summary', $texts->sections['executiveSummary']);
        $this->assertSame('Translated analysis', $texts->sections['analysisAndRecommendations']);
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

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('corrupted characters');

        $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);
    }

    public function testEmptyOptionalSectionIsOmitted(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse([
            'executiveSummary' => 'Summary',
            'methodology' => 'Methodology text',
            'emissionFactorSources' => 'Sources text',
            'analysisAndRecommendations' => 'Analysis text',
            'compensation' => '',
        ]);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertArrayNotHasKey('compensation', $texts->sections);
    }

    public function testRetriesWhenARequiredSectionIsMissingAndReturnsTheCompleteAttempt(): void
    {
        $client = new QueueGeminiClient([
            ['executiveSummary' => 'Only summary'],
            [
                'executiveSummary' => 'Summary',
                'methodology' => 'Methodology text',
                'emissionFactorSources' => 'Sources text',
                'analysisAndRecommendations' => 'Analysis text',
            ],
        ]);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $texts = $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);

        $this->assertSame(2, $client->calls());
        $this->assertSame('Methodology text', $texts->sections['methodology']);
    }

    public function testThrowsWhenTheResponseKeepsMissingRequiredSections(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse(['executiveSummary' => 'Only summary']);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete report texts');

        $sut->generate(EventCarbonFootprintReportDataMother::random(), Locale::Spanish);
    }

    public function testTranslateThrowsWhenATranslatedSectionComesBackEmpty(): void
    {
        $client = new InMemoryGeminiClient();
        $client->setResponse(['purpose' => 'Translated purpose', 'conclusion' => '']);

        $sut = new GeminiCarbonFootprintReportTextsGenerator($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('incomplete report texts');

        $sut->translate(new CarbonFootprintReportTexts(['purpose' => 'Propósito', 'conclusion' => 'Conclusión']), Locale::Spanish, Locale::English);
    }
}
