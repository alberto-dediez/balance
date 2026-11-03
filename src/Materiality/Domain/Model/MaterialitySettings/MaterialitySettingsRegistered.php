<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialitySettingsRegistered extends DomainEvent
{
    protected static string $eventName = 'materialitySettingsRegistered';

    public function __construct(
        private MaterialitySettingsId $materialitySettingsId,
        private int $relevanceThresholdPercent,
        private bool $highMagnitudeForcesInclusion,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialitySettingsId(): MaterialitySettingsId
    {
        return $this->materialitySettingsId;
    }

    public function relevanceThresholdPercent(): int
    {
        return $this->relevanceThresholdPercent;
    }

    public function highMagnitudeForcesInclusion(): bool
    {
        return $this->highMagnitudeForcesInclusion;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialitySettingsId;
    }

    public function payload(): array
    {
        return [
            'materialitySettingsId' => (string) $this->materialitySettingsId,
            'relevanceThresholdPercent' => $this->relevanceThresholdPercent,
            'highMagnitudeForcesInclusion' => $this->highMagnitudeForcesInclusion,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            materialitySettingsId: new MaterialitySettingsId($payload['materialitySettingsId']),
            relevanceThresholdPercent: (int) $payload['relevanceThresholdPercent'],
            highMagnitudeForcesInclusion: (bool) $payload['highMagnitudeForcesInclusion'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
