<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\SectorBenchmark\Refresh;

use App\Shared\Application\CommandBus\CommandResponse;

class RefreshSectorBenchmarksResponse implements CommandResponse
{
    public function __construct(
        public int $benchmarks,
    ) {}
}
