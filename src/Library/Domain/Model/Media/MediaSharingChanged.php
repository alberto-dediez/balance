<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaSharingChanged extends DomainEvent
{
    protected static string $eventName = 'mediaSharingChanged';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly bool $shared = false,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'shared' => $this->shared,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaSharingChanged(
            mediaId: new MediaId($payload['mediaId']),
            shared: (bool) $payload['shared'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
