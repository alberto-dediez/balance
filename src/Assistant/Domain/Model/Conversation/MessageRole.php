<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
