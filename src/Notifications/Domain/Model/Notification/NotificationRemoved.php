<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class NotificationRemoved extends DomainEvent
{
    protected static string $eventName = 'notificationRemoved';

    public function __construct(
        private NotificationId $notificationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->notificationId;
    }

    /**
     * @return array{notificationId: string}
     */
    public function payload(): array
    {
        return [
            'notificationId' => (string) $this->notificationId,
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
        return new NotificationRemoved(
            notificationId: new NotificationId($payload['notificationId'] ?? ''),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
