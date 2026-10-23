<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmailLogClicked extends DomainEvent
{
    protected static string $eventName = 'emailLogClicked';

    public function __construct(
        private EmailLogId $emailLogId,
        private \DateTimeImmutable $clickedAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function emailLogId(): EmailLogId
    {
        return $this->emailLogId;
    }

    public function clickedAt(): \DateTimeImmutable
    {
        return $this->clickedAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emailLogId;
    }

    /**
     * @return array{emailLogId: string, clickedAt: string}
     */
    public function payload(): array
    {
        return [
            'emailLogId' => (string) $this->emailLogId,
            'clickedAt' => $this->clickedAt->format(\DateTimeInterface::ATOM),
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
        return new EmailLogClicked(
            emailLogId: new EmailLogId($payload['emailLogId'] ?? ''),
            clickedAt: new \DateTimeImmutable($payload['clickedAt'] ?? 'NOW'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
