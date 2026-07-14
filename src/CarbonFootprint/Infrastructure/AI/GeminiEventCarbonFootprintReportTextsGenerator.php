<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTextsGenerator;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\TranslateReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GeminiEventCarbonFootprintReportTextsGenerator implements EventCarbonFootprintReportTextsGenerator
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(private readonly GeminiClient $geminiClient) {}

    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportTexts
    {
        return $this->requestTexts(new GenerateReportTextsPrompt($data, $locale));
    }

    public function translate(EventCarbonFootprintReportTexts $texts, Locale $from, Locale $to): EventCarbonFootprintReportTexts
    {
        return $this->requestTexts(new TranslateReportTextsPrompt($texts, $from, $to));
    }

    private function requestTexts(GeminiPrompt $prompt): EventCarbonFootprintReportTexts
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            $texts = $this->textsFrom($this->geminiClient->send($prompt));

            if (!$this->hasCorruptedCharacters($texts)) {
                return $texts;
            }
        }

        throw new \RuntimeException('Gemini returned corrupted characters in the report texts');
    }

    private function hasCorruptedCharacters(EventCarbonFootprintReportTexts $texts): bool
    {
        $content = $texts->executiveSummary . $texts->methodology . $texts->emissionFactorSources . $texts->analysisAndRecommendations;

        return 1 === preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content);
    }

    /** @param array<mixed> $result */
    private function textsFrom(array $result): EventCarbonFootprintReportTexts
    {
        return new EventCarbonFootprintReportTexts(
            executiveSummary: $this->cleanMarkdownContent($this->stringOrEmpty($result, 'executiveSummary')),
            methodology: $this->cleanMarkdownContent($this->stringOrEmpty($result, 'methodology')),
            emissionFactorSources: $this->cleanMarkdownContent($this->stringOrEmpty($result, 'emissionFactorSources')),
            analysisAndRecommendations: $this->cleanMarkdownContent($this->stringOrEmpty($result, 'analysisAndRecommendations')),
        );
    }

    /** @param array<mixed> $result */
    private function stringOrEmpty(array $result, string $key): string
    {
        return isset($result[$key]) && is_string($result[$key]) ? $result[$key] : '';
    }

    private function cleanMarkdownContent(string $content): string
    {
        $content = str_replace('\\n', "\n", $content);
        $content = str_replace('\\r', "\r", $content);
        $content = str_replace('\\t', "\t", $content);
        $content = str_replace('\\\\', '\\', $content);

        return trim($content);
    }
}
