<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

interface GeminiPrompt
{
    public function text(): string;

    /** @return array<mixed> */
    public function responseSchema(): array;

    public function modelTier(): GeminiModelTier;

    public function thinkingLevel(): GeminiThinkingLevel;
}
