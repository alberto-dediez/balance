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
        private ?MaterialityAssessmentSubjectType $subjectType = null,
        private ?string $carbonFootprintId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityAssessmentId(): MaterialityAssessmentId
    {
        return $this->materialityAssessmentId;
    }

    public function subjectType(): ?MaterialityAssessmentSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): ?string
    {
        return $this->carbonFootprintId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityAssessmentId;
    }

    public function payload(): array
    {
        return [
            'materialityAssessmentId' => (string) $this->materialityAssessmentId,
            'subjectType' => $this->subjectType?->value,
            'carbonFootprintId' => $this->carbonFootprintId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            materialityAssessmentId: new MaterialityAssessmentId($payload['materialityAssessmentId']),
            subjectType: MaterialityAssessmentSubjectType::tryFrom($payload['subjectType'] ?? ''),
            carbonFootprintId: $payload['carbonFootprintId'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
