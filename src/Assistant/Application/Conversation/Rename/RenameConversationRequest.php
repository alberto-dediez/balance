<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Rename;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\Command;

class RenameConversationRequest implements Command
{
    public function __construct(
        public ConversationId $conversationId,
        public string $title = '',
    ) {}
}
