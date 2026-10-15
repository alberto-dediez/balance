<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

enum GeminiThinkingLevel
{
    case MINIMAL;
    case LOW;
    case MEDIUM;
    case HIGH;
}
