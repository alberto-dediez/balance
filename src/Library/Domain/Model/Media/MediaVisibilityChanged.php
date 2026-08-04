<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaVisibilityChanged extends DomainEvent
{
    protected static string $eventName = 'mediaVisibilityChanged';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly MediaVisibility $visibility = MediaVisibility::Account,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function visibility(): MediaVisibility
    {
        return $this->visibility;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'visibility' => $this->visibility->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaVisibilityChanged(
            mediaId: new MediaId($payload['mediaId']),
            visibility: MediaVisibility::from($payload['visibility']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
