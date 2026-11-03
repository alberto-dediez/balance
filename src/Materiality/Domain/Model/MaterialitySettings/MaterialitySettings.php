<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

use App\Shared\Domain\Model\AggregateRoot;

class MaterialitySettings implements \Stringable
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialitySettingsId $id,
        private int $relevanceThresholdPercent = MaterialityDecisionRule::DEFAULT_THRESHOLD_PERCENT,
        private bool $highMagnitudeForcesInclusion = true,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): MaterialitySettingsId
    {
        return $this->id;
    }

    public function relevanceThresholdPercent(): int
    {
        return $this->relevanceThresholdPercent;
    }

    public function highMagnitudeForcesInclusion(): bool
    {
        return $this->highMagnitudeForcesInclusion;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rule(): MaterialityDecisionRule
    {
        return new MaterialityDecisionRule($this->relevanceThresholdPercent, $this->highMagnitudeForcesInclusion);
    }

    public function register(int $relevanceThresholdPercent, bool $highMagnitudeForcesInclusion): void
    {
        $rule = new MaterialityDecisionRule($relevanceThresholdPercent, $highMagnitudeForcesInclusion);

        $this->recordApplyAndPublish(
            new MaterialitySettingsRegistered(
                materialitySettingsId: $this->id,
                relevanceThresholdPercent: $rule->thresholdPercent,
                highMagnitudeForcesInclusion: $rule->highMagnitudeForcesInclusion,
            ),
        );
    }

    public function update(int $relevanceThresholdPercent, bool $highMagnitudeForcesInclusion): void
    {
        $rule = new MaterialityDecisionRule($relevanceThresholdPercent, $highMagnitudeForcesInclusion);

        if ($this->relevanceThresholdPercent === $rule->thresholdPercent && $this->highMagnitudeForcesInclusion === $rule->highMagnitudeForcesInclusion) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialitySettingsUpdated(
                materialitySettingsId: $this->id,
                relevanceThresholdPercent: $rule->thresholdPercent,
                highMagnitudeForcesInclusion: $rule->highMagnitudeForcesInclusion,
            ),
        );
    }

    public function applyMaterialitySettingsRegistered(MaterialitySettingsRegistered $event): void
    {
        $this->relevanceThresholdPercent = $event->relevanceThresholdPercent();
        $this->highMagnitudeForcesInclusion = $event->highMagnitudeForcesInclusion();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialitySettingsUpdated(MaterialitySettingsUpdated $event): void
    {
        $this->relevanceThresholdPercent = $event->relevanceThresholdPercent();
        $this->highMagnitudeForcesInclusion = $event->highMagnitudeForcesInclusion();
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
