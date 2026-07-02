<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

final class ActivityTableRow
{
    public function __construct(
        public readonly string $activityId,
        public readonly string $itemId,
        public readonly string $unit,
        public readonly float $quantity,
        public readonly string $co2eq,
        public readonly ?string $condition = null,
        public readonly ?string $itemId2 = null,
    ) {}
}
