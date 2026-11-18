<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\DownloadAttachment;

use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;

class DownloadConversationAttachmentRequest
{
    public function __construct(
        public ConversationId $conversationId,
        public MessageAttachmentId $attachmentId,
    ) {}
}
