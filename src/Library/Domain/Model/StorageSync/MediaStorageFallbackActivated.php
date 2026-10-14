<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\StorageSync;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaStorageFallbackActivated extends DomainEvent
{
    protected static string $eventName = 'mediaStorageFallbackActivated';

    public function __construct(
        private readonly string $path,
        private readonly string $error,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /**
     * @return array{path: string, error: string}
     */
    public function payload(): array
    {
        return [
            'path' => $this->path,
            'error' => $this->error,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaStorageFallbackActivated(
            path: $payload['path'] ?? '',
            error: $payload['error'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
