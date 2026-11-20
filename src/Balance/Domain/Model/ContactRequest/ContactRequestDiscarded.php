<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContactRequestDiscarded extends DomainEvent
{
    protected static string $eventName = 'contactRequestDiscarded';

    public function __construct(
        private ContactRequestId $contactRequestId,
        private UserId $resolvedBy,
        private \DateTimeImmutable $resolvedAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contactRequestId(): ContactRequestId
    {
        return $this->contactRequestId;
    }

    public function resolvedBy(): UserId
    {
        return $this->resolvedBy;
    }

    public function resolvedAt(): \DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contactRequestId();
    }

    public function payload(): array
    {
        return [
            'contactRequestId' => (string) $this->contactRequestId,
            'resolvedBy' => (string) $this->resolvedBy,
            'resolvedAt' => $this->resolvedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ContactRequestDiscarded(
            contactRequestId: new ContactRequestId($payload['contactRequestId']),
            resolvedBy: new UserId($payload['resolvedBy']),
            resolvedAt: new \DateTimeImmutable((string) $payload['resolvedAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
