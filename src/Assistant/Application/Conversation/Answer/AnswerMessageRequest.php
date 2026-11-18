<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Answer;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Shared\Application\CommandBus\Command;

class AnswerMessageRequest implements Command
{
    public function __construct(public ConversationId $conversationId, public MessageId $messageId) {}
}
