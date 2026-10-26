<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

enum AiThinkingLevel
{
    case MINIMAL;
    case LOW;
    case MEDIUM;
    case HIGH;
}
