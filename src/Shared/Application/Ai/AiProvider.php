<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

enum AiProvider: string
{
    case Gemini = 'gemini';
    case Anthropic = 'anthropic';
}
