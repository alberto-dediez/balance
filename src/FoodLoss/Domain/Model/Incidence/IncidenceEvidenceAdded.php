<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class IncidenceEvidenceAdded extends DomainEvent
{
    protected static string $eventName = 'incidenceEvidenceAdded';

    public function __construct(
        private IncidenceId $incidenceId,
        private IncidenceEvidenceId $evidenceId,
        private string $path,
        private string $originalName,
        private string $mimeType,
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

    public function path(): string
    {
        return $this->path;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
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
            'path' => $this->path,
            'originalName' => $this->originalName,
            'mimeType' => $this->mimeType,
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
            path: $payload['path'],
            originalName: $payload['originalName'],
            mimeType: $payload['mimeType'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
