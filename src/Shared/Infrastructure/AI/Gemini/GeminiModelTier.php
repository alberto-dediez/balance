<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

enum GeminiModelTier
{
    case LOW;
    case MEDIUM;
    case HIGH;
}
