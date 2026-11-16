<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\SendMessage;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Shared\Application\CommandBus\Command;

class SendMessageRequest implements Command
{
    /** @param list<MessageAttachmentUpload> $attachments */
    public function __construct(
        public ?ConversationId $conversationId = null,
        public string $text = '',
        public string $screenPath = '',
        public string $screenTitle = '',
        public string $timezone = 'UTC',
        public array $attachments = [],
    ) {}
}
