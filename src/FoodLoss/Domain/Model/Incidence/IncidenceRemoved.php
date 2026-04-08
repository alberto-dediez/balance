<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class IncidenceRemoved extends DomainEvent
{
    protected static string $eventName = 'incidenceRemoved';

    public function __construct(
        private IncidenceId $incidenceId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function incidenceId(): IncidenceId
    {
        return $this->incidenceId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->incidenceId();
    }

    public function payload(): array
    {
        return [
            'incidenceId' => (string) $this->incidenceId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            incidenceId: new IncidenceId($payload['incidenceId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
