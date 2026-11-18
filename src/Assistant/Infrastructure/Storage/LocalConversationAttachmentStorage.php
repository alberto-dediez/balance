<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Storage;

use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
class LocalConversationAttachmentStorage implements ConversationAttachmentStorage
{
    public function __construct(private readonly string $basePath) {}

    public function store(ConversationId $conversationId, MessageAttachmentId $attachmentId, string $extension, string $localPath): string
    {
        $relativePath = sprintf('%s/%s%s', (string) $conversationId, (string) $attachmentId, '' === $extension ? '' : '.' . $extension);

        $absolutePath = $this->toAbsolutePath($relativePath);

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create assistant directory "%s"', $directory));
        }

        if (false === copy($localPath, $absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to store assistant attachment at "%s"', $absolutePath));
        }

        return $relativePath;
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents($this->toAbsolutePath($path));

        if (false === $contents) {
            throw new \RuntimeException(sprintf('Unable to read assistant attachment "%s"', $path));
        }

        return $contents;
    }

    public function exists(string $path): bool
    {
        return '' !== $path && is_file($this->toAbsolutePath($path));
    }

    public function remove(string $path): void
    {
        if ('' === $path) {
            return;
        }

        $absolutePath = $this->toAbsolutePath($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    public function removeOf(ConversationId $conversationId): void
    {
        $directory = $this->toAbsolutePath((string) $conversationId);

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($directory);
    }

    private function toAbsolutePath(string $relativePath): string
    {
        if (str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException('Invalid assistant attachment path');
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($relativePath, '/');
    }
}
