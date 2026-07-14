<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Gemini;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class TranslateReportTextsPrompt implements GeminiPrompt
{
    public function __construct(
        private readonly EventCarbonFootprintReportTexts $texts,
        private readonly Locale $from,
        private readonly Locale $to,
        private readonly float $temperature = 0.2,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are a professional translator specialized in sustainability and carbon footprint reports.',
            sprintf('Translate the following four Markdown sections of an event carbon footprint report from %s to %s.', $this->from->name, $this->to->name),
            '',
            'Each section is delimited by an XML-like tag; the tags are not part of the content.',
            '',
            'Rules:',
            '- Preserve the Markdown structure exactly: keep every heading at its original level (a ### heading must stay ###), and keep bold text, bullet lists and paragraphs.',
            '- Do not alter numbers, units or references to emission factor sources.',
            '- Do not add, remove or summarize content; only translate it.',
            '',
            '<executiveSummary>',
            $this->texts->executiveSummary,
            '</executiveSummary>',
            '',
            '<methodology>',
            $this->texts->methodology,
            '</methodology>',
            '',
            '<emissionFactorSources>',
            $this->texts->emissionFactorSources,
            '</emissionFactorSources>',
            '',
            '<analysisAndRecommendations>',
            $this->texts->analysisAndRecommendations,
            '</analysisAndRecommendations>',
            '',
            'Respond strictly as a JSON object with this exact shape:',
            '{"executiveSummary": "string", "methodology": "string", "emissionFactorSources": "string", "analysisAndRecommendations": "string"}',
        ];

        return implode("\n", $lines);
    }

    public function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'executiveSummary' => ['type' => 'STRING'],
                'methodology' => ['type' => 'STRING'],
                'emissionFactorSources' => ['type' => 'STRING'],
                'analysisAndRecommendations' => ['type' => 'STRING'],
            ],
            'required' => ['executiveSummary', 'methodology', 'emissionFactorSources', 'analysisAndRecommendations'],
        ];
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    public function modelTier(): GeminiModelTier
    {
        return GeminiModelTier::LOW;
    }
}
