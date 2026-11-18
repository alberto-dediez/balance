<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

final readonly class MessageAttachmentDraft
{
    public function __construct(
        public MessageAttachmentId $id,
        public string $fileName,
        public string $mimeType,
        public int $size,
        public string $path,
    ) {}
}
