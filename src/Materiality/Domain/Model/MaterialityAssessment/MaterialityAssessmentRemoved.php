<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityAssessmentRemoved extends DomainEvent
{
    protected static string $eventName = 'materialityAssessmentRemoved';

    public function __construct(
        private MaterialityAssessmentId $materialityAssessmentId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityAssessmentId(): MaterialityAssessmentId
    {
        return $this->materialityAssessmentId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityAssessmentId;
    }

    public function payload(): array
    {
        return [
            'materialityAssessmentId' => (string) $this->materialityAssessmentId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            materialityAssessmentId: new MaterialityAssessmentId($payload['materialityAssessmentId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
