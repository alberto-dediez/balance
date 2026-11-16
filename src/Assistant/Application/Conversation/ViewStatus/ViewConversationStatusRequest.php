<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ViewStatus;

use App\Assistant\Domain\Model\Conversation\ConversationId;

class ViewConversationStatusRequest
{
    public function __construct(public ConversationId $conversationId) {}
}
