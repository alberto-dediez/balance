<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\DownloadAttachment;

class DownloadConversationAttachmentResponse
{
    public function __construct(
        public string $contents,
        public string $fileName,
        public string $mimeType,
        public int $size,
    ) {}
}
