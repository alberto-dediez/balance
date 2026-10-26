<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\StorageSync;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaStorageSynchronized extends DomainEvent
{
    protected static string $eventName = 'mediaStorageSynchronized';

    public function __construct(
        private readonly int $uploaded,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function uploaded(): int
    {
        return $this->uploaded;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /**
     * @return array{uploaded: int}
     */
    public function payload(): array
    {
        return [
            'uploaded' => $this->uploaded,
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
        return new MediaStorageSynchronized(
            uploaded: $payload['uploaded'] ?? 0,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
