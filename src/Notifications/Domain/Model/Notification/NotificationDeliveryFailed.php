<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class NotificationDeliveryFailed extends DomainEvent
{
    protected static string $eventName = 'notificationDeliveryFailed';

    public function __construct(
        private NotificationId $notificationId,
        private string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->notificationId;
    }

    /**
     * @return array{notificationId: string, reason: string}
     */
    public function payload(): array
    {
        return [
            'notificationId' => (string) $this->notificationId,
            'reason' => $this->reason,
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
        return new NotificationDeliveryFailed(
            notificationId: new NotificationId($payload['notificationId'] ?? ''),
            reason: $payload['reason'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
