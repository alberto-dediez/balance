<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

final readonly class SectorBenchmarkSample
{
    public function __construct(
        public string $accountId,
        public float $value,
    ) {}
}
