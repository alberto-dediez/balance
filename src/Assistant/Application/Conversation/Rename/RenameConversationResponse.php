<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Rename;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RenameConversationResponse implements CommandResponse
{
    public function __construct(public ConversationId $conversationId) {}
}
