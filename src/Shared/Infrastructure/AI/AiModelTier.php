<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

enum AiModelTier
{
    case LOW;
    case MEDIUM;
    case HIGH;
}
