<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Remove;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\Command;

class RemoveConversationRequest implements Command
{
    public function __construct(public ConversationId $conversationId) {}
}
