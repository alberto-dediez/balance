<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Remove;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveConversationResponse implements CommandResponse
{
    public function __construct(public ConversationId $conversationId) {}
}
