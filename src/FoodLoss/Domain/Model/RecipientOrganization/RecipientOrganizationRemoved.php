<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RecipientOrganizationRemoved extends DomainEvent
{
    protected static string $eventName = 'recipientOrganizationRemoved';

    public function __construct(
        private RecipientOrganizationId $recipientOrganizationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function recipientOrganizationId(): RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->recipientOrganizationId();
    }

    public function payload(): array
    {
        return [
            'recipientOrganizationId' => (string) $this->recipientOrganizationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new RecipientOrganizationRemoved(
            recipientOrganizationId: new RecipientOrganizationId($payload['recipientOrganizationId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
