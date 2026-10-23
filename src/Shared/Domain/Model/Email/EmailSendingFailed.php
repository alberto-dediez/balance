<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Email;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailSendingFailed extends DomainEvent
{
    protected static string $eventName = 'emailSendingFailed';

    public function __construct(
        private readonly EmailTrackingId $trackingId,
        private readonly EmailRecipient $recipient,
        private readonly string $subject,
        private readonly string $content,
        private readonly string $provider,
        private readonly string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trackingId(): EmailTrackingId
    {
        return $this->trackingId;
    }

    public function recipient(): EmailRecipient
    {
        return $this->recipient;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /**
     * @return array{trackingId: string, recipient: EmailRecipient, subject: string, content: string, provider: string, reason: string}
     */
    public function payload(): array
    {
        return [
            'trackingId' => (string) $this->trackingId,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'content' => $this->content,
            'provider' => $this->provider,
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
        return new EmailSendingFailed(
            trackingId: new EmailTrackingId($payload['trackingId'] ?? ''),
            recipient: $payload['recipient'] ?? new EmailRecipient('', ''),
            subject: $payload['subject'] ?? '',
            content: $payload['content'] ?? '',
            provider: $payload['provider'] ?? '',
            reason: $payload['reason'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
