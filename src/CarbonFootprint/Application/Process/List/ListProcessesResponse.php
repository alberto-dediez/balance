<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\List;

use App\CarbonFootprint\Domain\Model\Process\Process;

class ListProcessesResponse
{
    /** @param array<Process> $processes */
    public function __construct(public array $processes = [], public int $totalResults = 0) {}
}
