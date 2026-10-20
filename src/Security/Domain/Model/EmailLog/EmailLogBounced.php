<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailLogBounced extends DomainEvent
{
    protected static string $eventName = 'emailLogBounced';

    public function __construct(
        private EmailLogId $emailLogId,
        private string $reason,
        private \DateTimeImmutable $bouncedAt,
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

    public function bouncedAt(): \DateTimeImmutable
    {
        return $this->bouncedAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emailLogId;
    }

    /**
     * @return array{emailLogId: string, reason: string, bouncedAt: string}
     */
    public function payload(): array
    {
        return [
            'emailLogId' => (string) $this->emailLogId,
            'reason' => $this->reason,
            'bouncedAt' => $this->bouncedAt->format(\DateTimeInterface::ATOM),
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
        return new EmailLogBounced(
            emailLogId: new EmailLogId($payload['emailLogId'] ?? ''),
            reason: $payload['reason'] ?? '',
            bouncedAt: new \DateTimeImmutable($payload['bouncedAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
