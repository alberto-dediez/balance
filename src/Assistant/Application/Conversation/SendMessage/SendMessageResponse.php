<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\SendMessage;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Shared\Application\CommandBus\CommandResponse;

class SendMessageResponse implements CommandResponse
{
    public function __construct(public ConversationId $conversationId, public MessageId $assistantMessageId) {}
}
