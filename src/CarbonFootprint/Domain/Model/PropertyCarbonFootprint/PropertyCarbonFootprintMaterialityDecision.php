<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Security\Domain\Model\User\UserId;

class PropertyCarbonFootprintMaterialityDecision implements MaterialityDecision
{
    public function __construct(
        private PropertyCarbonFootprintMaterialityDecisionId $id,
        private PropertyCarbonFootprint $propertyCarbonFootprint,
        private string $topicId,
        private ?int $topicNumber,
        private ?Source $source,
        private float|string $scorePercent,
        private int $thresholdPercent,
        private bool $forcedByHighMagnitude,
        private MaterialityVerdict $verdict,
        private \DateTimeImmutable $recommendedAt = new \DateTimeImmutable('NOW'),
        private ?UserId $overriddenByUserId = null,
        private ?\DateTimeImmutable $overriddenAt = null,
        private ?string $overrideJustification = null,
    ) {}

    public function id(): PropertyCarbonFootprintMaterialityDecisionId
    {
        return $this->id;
    }

    public function propertyCarbonFootprint(): PropertyCarbonFootprint
    {
        return $this->propertyCarbonFootprint;
    }

    public function topicId(): string
    {
        return $this->topicId;
    }

    public function topicNumber(): ?int
    {
        return $this->topicNumber;
    }

    public function source(): ?Source
    {
        return $this->source;
    }

    public function verdict(): MaterialityVerdict
    {
        return $this->verdict;
    }

    public function scorePercent(): float
    {
        return (float) $this->scorePercent;
    }

    public function thresholdPercent(): int
    {
        return $this->thresholdPercent;
    }

    public function isForcedByHighMagnitude(): bool
    {
        return $this->forcedByHighMagnitude;
    }

    public function recommendedAt(): \DateTimeImmutable
    {
        return $this->recommendedAt;
    }

    public function isOverridden(): bool
    {
        return null !== $this->overriddenByUserId;
    }

    public function overriddenByUserId(): ?UserId
    {
        return $this->overriddenByUserId;
    }

    public function overriddenAt(): ?\DateTimeImmutable
    {
        return $this->overriddenAt;
    }

    public function overrideJustification(): ?string
    {
        return $this->overrideJustification;
    }

    public function recommendsExclusion(): bool
    {
        return $this->verdict->recommendsExclusion() && null !== $this->source;
    }

    public function recommend(?int $topicNumber, ?Source $source, float $scorePercent, int $thresholdPercent, bool $forcedByHighMagnitude, MaterialityVerdict $verdict, \DateTimeImmutable $recommendedAt): void
    {
        if ($this->verdict !== $verdict) {
            $this->revertOverride();
        }

        $this->topicNumber = $topicNumber;
        $this->source = $source;
        $this->scorePercent = $scorePercent;
        $this->thresholdPercent = $thresholdPercent;
        $this->forcedByHighMagnitude = $forcedByHighMagnitude;
        $this->verdict = $verdict;
        $this->recommendedAt = $recommendedAt;
    }

    public function override(UserId $userId, string $justification, \DateTimeImmutable $overriddenAt): void
    {
        $this->overriddenByUserId = $userId;
        $this->overriddenAt = $overriddenAt;
        $this->overrideJustification = $justification;
    }

    public function revertOverride(): void
    {
        $this->overriddenByUserId = null;
        $this->overriddenAt = null;
        $this->overrideJustification = null;
    }
}
