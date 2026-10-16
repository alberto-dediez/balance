<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

final class ActivityRowView
{
    public function __construct(
        public readonly string $activityId,
        public readonly string $itemId,
        public readonly string $unit,
        public readonly float $quantity,
        public readonly string $co2eq,
        public readonly ?string $condition = null,
        public readonly ?string $itemId2 = null,
        public readonly ?string $source = null,
        public readonly ?float $containerCapacity = null,
        public readonly ?string $containerUnit = null,
        public readonly ?float $fillPercentage = null,
        public readonly ?int $nights = null,
        public readonly ?int $people = null,
        public readonly ?int $acquisitionYear = null,
        public readonly ?float $weight = null,
        public readonly ?string $weightUnit = null,
        public readonly ?float $distanceKm = null,
    ) {}
}
