<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Notification implements \Stringable
{
    use AggregateRoot;

    /** @param DomainEvent[] $events */
    public function __construct(
        private NotificationId $id,
        private ?UserId $recipientId = null,
        private ?AccountId $accountId = null,
        private NotificationChannel $channel = NotificationChannel::Internal,
        private string $subject = '',
        private string $body = '',
        private NotificationStatus $status = NotificationStatus::Pending,
        private ?\DateTimeImmutable $readAt = null,
        private ?UserId $sentBy = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $sentAt = null,
        private ?string $failureReason = null,
    ) {
        $this->events = $events;
    }

    public function id(): NotificationId
    {
        return $this->id;
    }

    public function recipientId(): ?UserId
    {
        return $this->recipientId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }

    public function readAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function sentBy(): ?UserId
    {
        return $this->sentBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function isInternal(): bool
    {
        return NotificationChannel::Internal === $this->channel;
    }

    public function isRead(): bool
    {
        return null !== $this->readAt;
    }

    public function create(
        UserId $recipientId,
        NotificationChannel $channel,
        string $subject,
        string $body,
        ?AccountId $accountId = null,
        ?UserId $sentBy = null,
    ): void {
        $this->recordApplyAndPublish(
            new NotificationCreated(
                notificationId: $this->id(),
                recipientId: $recipientId,
                channel: $channel,
                subject: $subject,
                body: $body,
                accountId: $accountId,
                sentBy: $sentBy,
            ),
        );
    }

    public function markAsSent(): void
    {
        if (NotificationStatus::Sent === $this->status) {
            return;
        }

        $this->recordApplyAndPublish(
            new NotificationSent(
                notificationId: $this->id(),
                sentAt: new \DateTimeImmutable('NOW'),
            ),
        );
    }

    public function markAsFailed(string $reason): void
    {
        $this->recordApplyAndPublish(
            new NotificationDeliveryFailed(
                notificationId: $this->id(),
                reason: $reason,
            ),
        );
    }

    public function markAsRead(): void
    {
        if (null !== $this->readAt) {
            return;
        }

        $this->recordApplyAndPublish(
            new NotificationRead(
                notificationId: $this->id(),
                readAt: new \DateTimeImmutable('NOW'),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new NotificationRemoved(
                notificationId: $this->id(),
            ),
        );
    }

    public function applyNotificationCreated(NotificationCreated $event): void
    {
        $this->recipientId = $event->recipientId();
        $this->accountId = $event->accountId();
        $this->channel = $event->channel();
        $this->subject = $event->subject();
        $this->body = $event->body();
        $this->sentBy = $event->sentBy();
        $this->status = NotificationStatus::Pending;
        $this->createdAt = $event->occurredOn();
    }

    public function applyNotificationSent(NotificationSent $event): void
    {
        $this->status = NotificationStatus::Sent;
        $this->sentAt = $event->sentAt();
        $this->failureReason = null;
    }

    public function applyNotificationDeliveryFailed(NotificationDeliveryFailed $event): void
    {
        $this->status = NotificationStatus::Failed;
        $this->failureReason = $event->reason();
    }

    public function applyNotificationRead(NotificationRead $event): void
    {
        $this->readAt = $event->readAt();
    }

    public function applyNotificationRemoved(NotificationRemoved $event): void
    {
        $this->removed = true;
    }

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
