<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class EmailLog implements \Stringable
{
    use AggregateRoot;

    /** @param DomainEvent[] $events */
    public function __construct(
        private EmailLogId $id,
        private string $recipientEmail = '',
        private string $recipientName = '',
        private string $subject = '',
        private string $template = '',
        private string $provider = '',
        private ?string $providerMessageId = null,
        private EmailLogStatus $status = EmailLogStatus::Sent,
        private ?string $statusReason = null,
        private \DateTimeImmutable $sentAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $deliveredAt = null,
        private ?\DateTimeImmutable $firstOpenedAt = null,
        private ?\DateTimeImmutable $lastOpenedAt = null,
        private int $openCount = 0,
        private ?\DateTimeImmutable $firstClickedAt = null,
        private int $clickCount = 0,
        private ?\DateTimeImmutable $spamReportedAt = null,
        private ?\DateTimeImmutable $lastEventAt = null,
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): EmailLogId
    {
        return $this->id;
    }

    public function recipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function recipientName(): string
    {
        return $this->recipientName;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function template(): string
    {
        return $this->template;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function providerMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function status(): EmailLogStatus
    {
        return $this->status;
    }

    public function statusReason(): ?string
    {
        return $this->statusReason;
    }

    public function sentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function deliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function firstOpenedAt(): ?\DateTimeImmutable
    {
        return $this->firstOpenedAt;
    }

    public function lastOpenedAt(): ?\DateTimeImmutable
    {
        return $this->lastOpenedAt;
    }

    public function openCount(): int
    {
        return $this->openCount;
    }

    public function firstClickedAt(): ?\DateTimeImmutable
    {
        return $this->firstClickedAt;
    }

    public function clickCount(): int
    {
        return $this->clickCount;
    }

    public function spamReportedAt(): ?\DateTimeImmutable
    {
        return $this->spamReportedAt;
    }

    public function lastEventAt(): ?\DateTimeImmutable
    {
        return $this->lastEventAt;
    }

    public function isTrackable(): bool
    {
        return 'sendgrid' === $this->provider;
    }

    public function hasBeenOpened(): bool
    {
        return $this->openCount > 0;
    }

    public function register(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $template,
        string $provider,
        ?string $providerMessageId,
        EmailLogStatus $status,
        ?string $statusReason,
        \DateTimeImmutable $sentAt,
    ): void {
        if (!in_array($status, [EmailLogStatus::Sent, EmailLogStatus::Failed], true)) {
            throw new \InvalidArgumentException(sprintf('Email log cannot be registered with status "%s"', $status->value));
        }

        $this->recordApplyAndPublish(
            new EmailLogRegistered(
                emailLogId: $this->id(),
                recipientEmail: $recipientEmail,
                recipientName: $recipientName,
                subject: $subject,
                template: $template,
                provider: $provider,
                providerMessageId: $providerMessageId,
                status: $status,
                statusReason: $statusReason,
                sentAt: $sentAt,
            ),
        );
    }

    public function markAsDelivered(\DateTimeImmutable $at): void
    {
        if (!in_array($this->status, [EmailLogStatus::Sent, EmailLogStatus::Deferred], true)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmailLogDelivered(
                emailLogId: $this->id(),
                deliveredAt: $at,
            ),
        );
    }

    public function markAsDeferred(string $reason, \DateTimeImmutable $at): void
    {
        if (!in_array($this->status, [EmailLogStatus::Sent, EmailLogStatus::Deferred], true)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmailLogDeferred(
                emailLogId: $this->id(),
                reason: $reason,
                deferredAt: $at,
            ),
        );
    }

    public function markAsBounced(string $reason, \DateTimeImmutable $at): void
    {
        if ($this->status->isTerminal()) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmailLogBounced(
                emailLogId: $this->id(),
                reason: $reason,
                bouncedAt: $at,
            ),
        );
    }

    public function markAsDropped(string $reason, \DateTimeImmutable $at): void
    {
        if (!in_array($this->status, [EmailLogStatus::Sent, EmailLogStatus::Deferred], true)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmailLogDropped(
                emailLogId: $this->id(),
                reason: $reason,
                droppedAt: $at,
            ),
        );
    }

    public function registerOpen(\DateTimeImmutable $at): void
    {
        $this->recordApplyAndPublish(
            new EmailLogOpened(
                emailLogId: $this->id(),
                openedAt: $at,
            ),
        );
    }

    public function registerClick(\DateTimeImmutable $at): void
    {
        $this->recordApplyAndPublish(
            new EmailLogClicked(
                emailLogId: $this->id(),
                clickedAt: $at,
            ),
        );
    }

    public function markAsSpamReported(\DateTimeImmutable $at): void
    {
        $this->recordApplyAndPublish(
            new EmailLogSpamReported(
                emailLogId: $this->id(),
                reportedAt: $at,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EmailLogRemoved(
                emailLogId: $this->id(),
            ),
        );
    }

    public function applyEmailLogRegistered(EmailLogRegistered $event): void
    {
        $this->recipientEmail = $event->recipientEmail();
        $this->recipientName = $event->recipientName();
        $this->subject = $event->subject();
        $this->template = $event->template();
        $this->provider = $event->provider();
        $this->providerMessageId = $event->providerMessageId();
        $this->status = $event->status();
        $this->statusReason = $event->statusReason();
        $this->sentAt = $event->sentAt();
    }

    public function applyEmailLogDelivered(EmailLogDelivered $event): void
    {
        $this->status = EmailLogStatus::Delivered;
        $this->deliveredAt = $event->deliveredAt();
        $this->statusReason = null;
        $this->lastEventAt = $event->deliveredAt();
    }

    public function applyEmailLogDeferred(EmailLogDeferred $event): void
    {
        $this->status = EmailLogStatus::Deferred;
        $this->statusReason = $event->reason();
        $this->lastEventAt = $event->deferredAt();
    }

    public function applyEmailLogBounced(EmailLogBounced $event): void
    {
        $this->status = EmailLogStatus::Bounced;
        $this->statusReason = $event->reason();
        $this->lastEventAt = $event->bouncedAt();
    }

    public function applyEmailLogDropped(EmailLogDropped $event): void
    {
        $this->status = EmailLogStatus::Dropped;
        $this->statusReason = $event->reason();
        $this->lastEventAt = $event->droppedAt();
    }

    public function applyEmailLogOpened(EmailLogOpened $event): void
    {
        $this->firstOpenedAt ??= $event->openedAt();
        $this->lastOpenedAt = $event->openedAt();
        ++$this->openCount;
        $this->lastEventAt = $event->openedAt();
    }

    public function applyEmailLogClicked(EmailLogClicked $event): void
    {
        $this->firstClickedAt ??= $event->clickedAt();
        ++$this->clickCount;
        $this->lastEventAt = $event->clickedAt();
    }

    public function applyEmailLogSpamReported(EmailLogSpamReported $event): void
    {
        $this->spamReportedAt = $event->reportedAt();
        $this->lastEventAt = $event->reportedAt();
    }

    public function applyEmailLogRemoved(EmailLogRemoved $event): void {}

    public function __toString(): string
    {
        return (string) $this->id();
    }
}
