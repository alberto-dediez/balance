<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationActionEffectRemoved extends DomainEvent
{
    protected static string $eventName = 'mitigationActionEffectRemoved';

    public function __construct(
        private readonly MitigationActionId $mitigationActionId,
        private readonly Source $source,
        private readonly string $itemId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationActionId(): MitigationActionId
    {
        return $this->mitigationActionId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function itemId(): string
    {
        return $this->itemId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationActionId;
    }

    public function payload(): array
    {
        return [
            'mitigationActionId' => (string) $this->mitigationActionId,
            'source' => $this->source->value,
            'itemId' => $this->itemId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationActionId: new MitigationActionId($payload['mitigationActionId']),
            source: Source::from($payload['source']),
            itemId: $payload['itemId'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
