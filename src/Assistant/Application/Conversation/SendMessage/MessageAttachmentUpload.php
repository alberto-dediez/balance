<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\SendMessage;

final readonly class MessageAttachmentUpload
{
    public function __construct(
        public string $localPath,
        public string $fileName,
        public string $mimeType,
        public int $size,
        public string $extension,
    ) {}
}
