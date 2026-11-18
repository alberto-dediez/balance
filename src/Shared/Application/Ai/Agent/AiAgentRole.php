<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

enum AiAgentRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
