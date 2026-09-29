<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

class MitigationMeasureEffect
{
    public function __construct(
        private MitigationMeasureEffectId $id,
        private MitigationMeasure $measure,
        private Source $source,
        private float $minimumReduction,
        private float $maximumReduction,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): MitigationMeasureEffectId
    {
        return $this->id;
    }

    public function measure(): MitigationMeasure
    {
        return $this->measure;
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

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(float $minimumReduction, float $maximumReduction, \DateTimeImmutable $updatedAt): void
    {
        $this->minimumReduction = $minimumReduction;
        $this->maximumReduction = $maximumReduction;
        $this->updatedAt = $updatedAt;
    }
}
