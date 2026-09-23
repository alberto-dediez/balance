<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Gemini;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class TranslateReportTextsPrompt implements GeminiPrompt
{
    public function __construct(
        private readonly CarbonFootprintReportTexts $texts,
        private readonly Locale $from,
        private readonly Locale $to,
        private readonly float $temperature = 0.2,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are a professional translator specialized in sustainability and carbon footprint reports.',
            sprintf(
                'Translate the following %d Markdown sections of a carbon footprint report from %s to %s.',
                count($this->texts->sections),
                $this->from->name,
                $this->to->name,
            ),
            '',
            'Each section is delimited by an XML-like tag; the tags are not part of the content.',
            '',
            'Rules:',
            '- Preserve the Markdown structure exactly: keep every heading at its original level (a ### heading must stay ###), and keep bold text, bullet lists and paragraphs.',
            '- Do not alter numbers, units or references to emission factor sources.',
            '- Do not add, remove or summarize content; only translate it.',
        ];

        foreach ($this->texts->sections as $key => $content) {
            $lines[] = '';
            $lines[] = sprintf('<%s>', $key);
            $lines[] = $content;
            $lines[] = sprintf('</%s>', $key);
        }

        $lines[] = '';
        $lines[] = 'Respond strictly as a JSON object with this exact shape:';
        $lines[] = $this->shape();

        return implode("\n", $lines);
    }

    private function shape(): string
    {
        $pairs = array_map(static fn(string $key): string => sprintf('"%s": "string"', $key), array_keys($this->texts->sections));

        return '{' . implode(', ', $pairs) . '}';
    }

    public function responseSchema(): array
    {
        $properties = [];

        foreach (array_keys($this->texts->sections) as $key) {
            $properties[$key] = ['type' => 'STRING'];
        }

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => array_keys($this->texts->sections),
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
