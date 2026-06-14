<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class NotificationSent extends DomainEvent
{
    protected static string $eventName = 'notificationSent';

    public function __construct(
        private NotificationId $notificationId,
        private \DateTimeImmutable $sentAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function sentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->notificationId;
    }

    /**
     * @return array{notificationId: string, sentAt: string}
     */
    public function payload(): array
    {
        return [
            'notificationId' => (string) $this->notificationId,
            'sentAt' => $this->sentAt->format(\DateTimeInterface::ATOM),
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
        return new NotificationSent(
            notificationId: new NotificationId($payload['notificationId'] ?? ''),
            sentAt: new \DateTimeImmutable($payload['sentAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
