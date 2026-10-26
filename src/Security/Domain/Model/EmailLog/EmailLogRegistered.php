<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailLogRegistered extends DomainEvent
{
    protected static string $eventName = 'emailLogRegistered';

    public function __construct(
        private EmailLogId $emailLogId,
        private string $recipientEmail,
        private string $recipientName,
        private string $subject,
        private string $template,
        private string $provider,
        private ?string $providerMessageId,
        private EmailLogStatus $status,
        private ?string $statusReason,
        private \DateTimeImmutable $sentAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function emailLogId(): EmailLogId
    {
        return $this->emailLogId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emailLogId;
    }

    /**
     * @return array{emailLogId: string, recipientEmail: string, recipientName: string, subject: string, template: string, provider: string, providerMessageId: ?string, status: string, statusReason: ?string, sentAt: string}
     */
    public function payload(): array
    {
        return [
            'emailLogId' => (string) $this->emailLogId,
            'recipientEmail' => $this->recipientEmail,
            'recipientName' => $this->recipientName,
            'subject' => $this->subject,
            'template' => $this->template,
            'provider' => $this->provider,
            'providerMessageId' => $this->providerMessageId,
            'status' => $this->status->value,
            'statusReason' => $this->statusReason,
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
        return new EmailLogRegistered(
            emailLogId: new EmailLogId($payload['emailLogId'] ?? ''),
            recipientEmail: $payload['recipientEmail'] ?? '',
            recipientName: $payload['recipientName'] ?? '',
            subject: $payload['subject'] ?? '',
            template: $payload['template'] ?? '',
            provider: $payload['provider'] ?? '',
            providerMessageId: $payload['providerMessageId'] ?? null,
            status: EmailLogStatus::tryFrom($payload['status'] ?? '') ?? EmailLogStatus::Sent,
            statusReason: $payload['statusReason'] ?? null,
            sentAt: new \DateTimeImmutable($payload['sentAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
