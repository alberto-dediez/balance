<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailLogDropped extends DomainEvent
{
    protected static string $eventName = 'emailLogDropped';

    public function __construct(
        private EmailLogId $emailLogId,
        private string $reason,
        private \DateTimeImmutable $droppedAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function emailLogId(): EmailLogId
    {
        return $this->emailLogId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function droppedAt(): \DateTimeImmutable
    {
        return $this->droppedAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emailLogId;
    }

    /**
     * @return array{emailLogId: string, reason: string, droppedAt: string}
     */
    public function payload(): array
    {
        return [
            'emailLogId' => (string) $this->emailLogId,
            'reason' => $this->reason,
            'droppedAt' => $this->droppedAt->format(\DateTimeInterface::ATOM),
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
        return new EmailLogDropped(
            emailLogId: new EmailLogId($payload['emailLogId'] ?? ''),
            reason: $payload['reason'] ?? '',
            droppedAt: new \DateTimeImmutable($payload['droppedAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
