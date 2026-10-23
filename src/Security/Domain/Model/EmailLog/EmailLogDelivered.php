<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailLogDelivered extends DomainEvent
{
    protected static string $eventName = 'emailLogDelivered';

    public function __construct(
        private EmailLogId $emailLogId,
        private \DateTimeImmutable $deliveredAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function emailLogId(): EmailLogId
    {
        return $this->emailLogId;
    }

    public function deliveredAt(): \DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emailLogId;
    }

    /**
     * @return array{emailLogId: string, deliveredAt: string}
     */
    public function payload(): array
    {
        return [
            'emailLogId' => (string) $this->emailLogId,
            'deliveredAt' => $this->deliveredAt->format(\DateTimeInterface::ATOM),
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
        return new EmailLogDelivered(
            emailLogId: new EmailLogId($payload['emailLogId'] ?? ''),
            deliveredAt: new \DateTimeImmutable($payload['deliveredAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
