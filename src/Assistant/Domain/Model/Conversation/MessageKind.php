<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

enum MessageKind: string
{
    case Text = 'text';
    case Plan = 'plan';
    case Execution = 'execution';
}
