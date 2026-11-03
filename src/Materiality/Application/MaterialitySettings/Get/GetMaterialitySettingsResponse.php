<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\Get;

class GetMaterialitySettingsResponse
{
    public function __construct(
        public int $relevanceThresholdPercent,
        public bool $highMagnitudeForcesInclusion,
        public bool $persisted,
    ) {}
}
