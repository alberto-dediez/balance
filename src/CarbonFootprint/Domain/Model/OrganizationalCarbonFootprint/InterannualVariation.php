<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

final readonly class InterannualVariation
{
    public function __construct(
        public int $fromYear,
        public int $toYear,
        public float $fromTotal,
        public float $toTotal,
        public float $delta,
        public ?float $activityEffect,
        public float $factorEffect,
        public float $efficiencyEffect,
        public ?IntensityIndicator $primaryIndicator,
    ) {}

    public function deltaPercent(): ?float
    {
        if (0.0 === $this->fromTotal) {
            return null;
        }

        return $this->delta / $this->fromTotal * 100;
    }
}
