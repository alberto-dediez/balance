<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class NotificationRead extends DomainEvent
{
    protected static string $eventName = 'notificationRead';

    public function __construct(
        private NotificationId $notificationId,
        private \DateTimeImmutable $readAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function readAt(): \DateTimeImmutable
    {
        return $this->readAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->notificationId;
    }

    /**
     * @return array{notificationId: string, readAt: string}
     */
    public function payload(): array
    {
        return [
            'notificationId' => (string) $this->notificationId,
            'readAt' => $this->readAt->format(\DateTimeInterface::ATOM),
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
        return new NotificationRead(
            notificationId: new NotificationId($payload['notificationId'] ?? ''),
            readAt: new \DateTimeImmutable($payload['readAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
