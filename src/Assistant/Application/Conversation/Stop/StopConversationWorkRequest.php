<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Stop;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\Command;

class StopConversationWorkRequest implements Command
{
    public function __construct(public ConversationId $conversationId) {}
}
