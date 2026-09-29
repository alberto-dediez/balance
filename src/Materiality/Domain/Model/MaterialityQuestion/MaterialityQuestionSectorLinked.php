<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\DomainEvent;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityQuestionSectorLinked extends DomainEvent
{
    protected static string $eventName = 'materialityQuestionSectorLinked';

    public function __construct(
        private MaterialityQuestionId $materialityQuestionId,
        private MaterialityQuestionSectorLinkId $linkId,
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

    public function linkId(): MaterialityQuestionSectorLinkId
    {
        return $this->linkId;
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
            'linkId' => (string) $this->linkId,
            'sectorId' => (string) $this->sectorId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityQuestionSectorLinked(
            materialityQuestionId: new MaterialityQuestionId($payload['materialityQuestionId']),
            linkId: isset($payload['linkId']) ? new MaterialityQuestionSectorLinkId($payload['linkId']) : MaterialityQuestionSectorLinkId::generate(),
            sectorId: new MaterialitySectorId($payload['sectorId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
