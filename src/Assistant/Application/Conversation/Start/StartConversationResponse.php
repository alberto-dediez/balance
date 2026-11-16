<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Start;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\CommandResponse;

class StartConversationResponse implements CommandResponse
{
    public function __construct(public ConversationId $conversationId) {}
}
