<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

enum AiProvider: string
{
    case Gemini = 'gemini';
    case Anthropic = 'anthropic';
}
