<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai;

enum AiEffort: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
