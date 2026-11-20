<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaMoved extends DomainEvent
{
    protected static string $eventName = 'mediaMoved';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly ?FolderId $folderId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function folderId(): ?FolderId
    {
        return $this->folderId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'folderId' => $this->folderId instanceof FolderId ? (string) $this->folderId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaMoved(
            mediaId: new MediaId($payload['mediaId']),
            folderId: null === $payload['folderId'] ? null : new FolderId($payload['folderId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
