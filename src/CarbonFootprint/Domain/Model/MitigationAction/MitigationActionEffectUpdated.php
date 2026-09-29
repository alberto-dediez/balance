<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationActionEffectUpdated extends DomainEvent
{
    protected static string $eventName = 'mitigationActionEffectUpdated';

    public function __construct(
        private readonly MitigationActionId $mitigationActionId,
        private readonly Source $source,
        private readonly string $itemId,
        private readonly float $reduction,
        private readonly float $minimumReduction,
        private readonly float $maximumReduction,
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

    public function reduction(): float
    {
        return $this->reduction;
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
        return $this->mitigationActionId;
    }

    public function payload(): array
    {
        return [
            'mitigationActionId' => (string) $this->mitigationActionId,
            'source' => $this->source->value,
            'itemId' => $this->itemId,
            'reduction' => $this->reduction,
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
            mitigationActionId: new MitigationActionId($payload['mitigationActionId']),
            source: Source::from($payload['source']),
            itemId: $payload['itemId'],
            reduction: (float) $payload['reduction'],
            minimumReduction: (float) $payload['minimumReduction'],
            maximumReduction: (float) $payload['maximumReduction'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
