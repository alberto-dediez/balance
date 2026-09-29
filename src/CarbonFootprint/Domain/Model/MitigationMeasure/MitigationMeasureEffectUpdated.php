<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationMeasureEffectUpdated extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureEffectUpdated';

    public function __construct(
        private readonly MitigationMeasureId $mitigationMeasureId,
        private readonly Source $source,
        private readonly float $minimumReduction,
        private readonly float $maximumReduction,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureId(): MitigationMeasureId
    {
        return $this->mitigationMeasureId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function minimumReduction(): float
    {
        return $this->minimumReduction;
    }

    public function maximumReduction(): float
    {
        return $this->maximumReduction;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationMeasureId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureId' => (string) $this->mitigationMeasureId,
            'source' => $this->source->value,
            'minimumReduction' => $this->minimumReduction,
            'maximumReduction' => $this->maximumReduction,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            mitigationMeasureId: new MitigationMeasureId($payload['mitigationMeasureId']),
            source: Source::from($payload['source']),
            minimumReduction: (float) $payload['minimumReduction'],
            maximumReduction: (float) $payload['maximumReduction'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
