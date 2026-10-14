<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

interface GeminiClient
{
    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array;
}
