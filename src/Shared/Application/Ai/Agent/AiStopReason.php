<?php

declare(strict_types=1);

namespace App\Shared\Application\Ai\Agent;

enum AiStopReason: string
{
    case EndTurn = 'end_turn';
    case ToolUse = 'tool_use';
    case MaxTokens = 'max_tokens';
    case Refusal = 'refusal';
    case Other = 'other';
}
