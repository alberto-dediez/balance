<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Geo;

final readonly class RouteDistance
{
    public function __construct(
        public float $kilometers,
        public ?int $durationSeconds,
        public DistanceSource $source,
    ) {}
}
