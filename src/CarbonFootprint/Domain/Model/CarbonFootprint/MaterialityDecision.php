<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\Security\Domain\Model\User\UserId;

interface MaterialityDecision
{
    public function topicId(): string;

    public function topicNumber(): ?int;

    public function source(): ?Source;

    public function verdict(): MaterialityVerdict;

    public function scorePercent(): float;

    public function thresholdPercent(): int;

    public function isForcedByHighMagnitude(): bool;

    public function recommendedAt(): \DateTimeImmutable;

    public function isOverridden(): bool;

    public function overriddenByUserId(): ?UserId;

    public function overriddenAt(): ?\DateTimeImmutable;

    public function overrideJustification(): ?string;

    public function recommendsExclusion(): bool;
}
