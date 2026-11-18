<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

interface ConversationAttachmentStorage
{
    public function store(ConversationId $conversationId, MessageAttachmentId $attachmentId, string $extension, string $localPath): string;

    public function read(string $path): string;

    public function exists(string $path): bool;

    public function remove(string $path): void;

    public function removeOf(ConversationId $conversationId): void;
}
