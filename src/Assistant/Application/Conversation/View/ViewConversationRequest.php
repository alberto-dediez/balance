<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\View;

use App\Assistant\Domain\Model\Conversation\ConversationId;

class ViewConversationRequest
{
    public function __construct(public ConversationId $conversationId) {}
}
