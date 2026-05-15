<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class TranslateDraftPrompt implements GeminiPrompt
{
    /**
     * @param array<int, array{title: string, content: string}> $sections
     */
    public function __construct(
        private readonly array $sections,
        private readonly Locale $sourceLocale,
        private readonly Locale $targetLocale,
        private readonly float $temperature = 0.1,
    ) {}

    public function text(): string
    {
        $lines = [
            sprintf(
                'Translate the following food loss prevention plan sections from %s to %s.',
                $this->sourceLocale->name,
                $this->targetLocale->name,
            ),
            '',
            'Rules:',
            '- Preserve all Markdown formatting exactly (headings, bold, bullet lists, line breaks).',
            '- Translate only the text content; do not alter structure or formatting characters.',
            '- Maintain a formal, technical tone appropriate for a food safety compliance document.',
            '- Return one object per section preserving the original "index" value.',
            '',
            'Sections to translate:',
        ];

        foreach ($this->sections as $index => $section) {
            $lines[] = '';
            $lines[] = sprintf('Index: %d', $index);
            $lines[] = sprintf('Title: %s', $section['title']);
            $lines[] = 'Content:';
            $lines[] = $section['content'];
        }

        return implode("\n", $lines);
    }

    /** @return array<mixed> */
    public function responseSchema(): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'index' => ['type' => 'INTEGER'],
                    'title' => ['type' => 'STRING'],
                    'content' => ['type' => 'STRING'],
                ],
                'required' => ['index', 'title', 'content'],
            ],
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
