<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class IncidenceEvidenceRemoved extends DomainEvent
{
    protected static string $eventName = 'incidenceEvidenceRemoved';

    public function __construct(
        private IncidenceId $incidenceId,
        private IncidenceEvidenceId $evidenceId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function incidenceId(): IncidenceId
    {
        return $this->incidenceId;
    }

    public function evidenceId(): IncidenceEvidenceId
    {
        return $this->evidenceId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->incidenceId();
    }

    public function payload(): array
    {
        return [
            'incidenceId' => (string) $this->incidenceId,
            'evidenceId' => (string) $this->evidenceId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            incidenceId: new IncidenceId($payload['incidenceId']),
            evidenceId: new IncidenceEvidenceId($payload['evidenceId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
