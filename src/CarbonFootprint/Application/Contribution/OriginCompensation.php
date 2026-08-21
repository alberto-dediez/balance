<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contribution;

final class OriginCompensation
{
    public function __construct(
        public readonly string $projects,
        public readonly float $amount,
        public readonly float $co2eqKg,
    ) {}
}
