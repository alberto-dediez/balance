<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\DomainEvent;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityQuestionSectorUnlinked extends DomainEvent
{
    protected static string $eventName = 'materialityQuestionSectorUnlinked';

    public function __construct(
        private MaterialityQuestionId $materialityQuestionId,
        private MaterialitySectorId $sectorId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityQuestionId(): MaterialityQuestionId
    {
        return $this->materialityQuestionId;
    }

    public function sectorId(): MaterialitySectorId
    {
        return $this->sectorId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityQuestionId();
    }

    public function payload(): array
    {
        return [
            'materialityQuestionId' => (string) $this->materialityQuestionId,
            'sectorId' => (string) $this->sectorId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityQuestionSectorUnlinked(
            materialityQuestionId: new MaterialityQuestionId($payload['materialityQuestionId']),
            sectorId: new MaterialitySectorId($payload['sectorId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
