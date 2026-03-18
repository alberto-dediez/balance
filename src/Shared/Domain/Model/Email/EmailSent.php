<?php

namespace App\Shared\Domain\Model\Email;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailSent extends DomainEvent
{
    protected static string $eventName = 'emailSended';

    public function __construct(
        private readonly EmailRecipient $recipient,
        private readonly string $subject,
        private readonly string $content,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
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

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /**
     * @return array{recipient: EmailRecipient, subject: string, content: string}
     */
    public function payload(): array
    {
        return [
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'content' => $this->content
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId     $id,
        DateTimeImmutable $occurredOn,
        array             $payload = []
    ): static {
        return new EmailSent(
            recipient: $payload['recipient'] ?? new EmailRecipient('', ''),
            subject: $payload['subject'] ?? '',
            content: $payload['content'] ?? '',
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
