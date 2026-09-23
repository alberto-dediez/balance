<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AnnouncementPublished extends DomainEvent
{
    protected static string $eventName = 'announcementPublished';

    public function __construct(
        private readonly AnnouncementId $announcementId,
        private readonly \DateTimeImmutable $publishedAt,
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

    public function publishedAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
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
            'publishedAt' => $this->publishedAt->format(\DateTimeInterface::ATOM),
            'notifyUsers' => $this->notifyUsers,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AnnouncementPublished(
            announcementId: new AnnouncementId($payload['announcementId']),
            publishedAt: new \DateTimeImmutable($payload['publishedAt']),
            notifyUsers: $payload['notifyUsers'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
