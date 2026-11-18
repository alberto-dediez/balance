<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Storage;

use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryConversationAttachmentStorage implements ConversationAttachmentStorage
{
    /** @var array<string, string> */
    private array $contents = [];

    public function store(ConversationId $conversationId, MessageAttachmentId $attachmentId, string $extension, string $localPath): string
    {
        $relativePath = sprintf('%s/%s%s', (string) $conversationId, (string) $attachmentId, '' === $extension ? '' : '.' . $extension);

        $contents = is_file($localPath) ? file_get_contents($localPath) : '';

        $this->contents[$relativePath] = false === $contents ? '' : $contents;

        return $relativePath;
    }

    public function read(string $path): string
    {
        if (!$this->exists($path)) {
            throw new \RuntimeException(sprintf('Unable to read assistant attachment "%s"', $path));
        }

        return $this->contents[$path];
    }

    public function exists(string $path): bool
    {
        return isset($this->contents[$path]);
    }

    public function remove(string $path): void
    {
        unset($this->contents[$path]);
    }

    public function removeOf(ConversationId $conversationId): void
    {
        foreach ($this->pathsOf($conversationId) as $path) {
            unset($this->contents[$path]);
        }
    }

    /** @return list<string> */
    public function pathsOf(ConversationId $conversationId): array
    {
        $prefix = (string) $conversationId . '/';

        return array_values(array_filter(
            array_map(static fn(int|string $path): string => (string) $path, array_keys($this->contents)),
            static fn(string $path): bool => str_starts_with($path, $prefix),
        ));
    }

    public function put(string $path, string $contents): void
    {
        $this->contents[$path] = $contents;
    }

    public function reset(): void
    {
        $this->contents = [];
    }
}
