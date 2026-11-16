<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

class MessageAttachment
{
    public const int FILE_NAME_MAX_LENGTH = 255;
    public const int MIME_TYPE_MAX_LENGTH = 100;
    public const int PATH_MAX_LENGTH = 512;

    public function __construct(
        private MessageAttachmentId $id,
        private Message $message,
        private int $position,
        private string $fileName,
        private string $mimeType,
        private int $size,
        private string $path,
    ) {}

    public static function fromDraft(Message $message, int $position, MessageAttachmentDraft $draft): self
    {
        return new self(
            $draft->id,
            $message,
            $position,
            mb_substr($draft->fileName, 0, self::FILE_NAME_MAX_LENGTH),
            mb_substr($draft->mimeType, 0, self::MIME_TYPE_MAX_LENGTH),
            $draft->size,
            $draft->path,
        );
    }

    public function id(): MessageAttachmentId
    {
        return $this->id;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function path(): string
    {
        return $this->path;
    }
}
