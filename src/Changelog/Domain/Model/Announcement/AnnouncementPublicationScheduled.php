<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AnnouncementPublicationScheduled extends DomainEvent
{
    protected static string $eventName = 'announcementPublicationScheduled';

    public function __construct(
        private readonly AnnouncementId $announcementId,
        private readonly \DateTimeImmutable $scheduledAt,
        private readonly bool $notifyUsers,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function announcementId(): AnnouncementId
    {
        return $this->announcementId;
    }

    public function scheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function notifyUsers(): bool
    {
        return $this->notifyUsers;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->announcementId;
    }

    public function payload(): array
    {
        return [
            'announcementId' => (string) $this->announcementId,
            'scheduledAt' => $this->scheduledAt->format(\DateTimeInterface::ATOM),
            'notifyUsers' => $this->notifyUsers,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AnnouncementPublicationScheduled(
            announcementId: new AnnouncementId($payload['announcementId']),
            scheduledAt: new \DateTimeImmutable($payload['scheduledAt']),
            notifyUsers: $payload['notifyUsers'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
