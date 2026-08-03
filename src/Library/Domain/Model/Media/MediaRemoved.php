<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaRemoved extends DomainEvent
{
    protected static string $eventName = 'mediaRemoved';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly string $path = '',
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'path' => $this->path,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaRemoved(
            mediaId: new MediaId($payload['mediaId']),
            path: $payload['path'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
