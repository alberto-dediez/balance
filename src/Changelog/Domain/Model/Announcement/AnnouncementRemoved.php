<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AnnouncementRemoved extends DomainEvent
{
    protected static string $eventName = 'announcementRemoved';

    public function __construct(
        private readonly AnnouncementId $announcementId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function announcementId(): AnnouncementId
    {
        return $this->announcementId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->announcementId;
    }

    public function payload(): array
    {
        return [
            'announcementId' => (string) $this->announcementId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AnnouncementRemoved(
            announcementId: new AnnouncementId($payload['announcementId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
