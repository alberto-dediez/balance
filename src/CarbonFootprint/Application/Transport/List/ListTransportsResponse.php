<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\List;

use App\CarbonFootprint\Domain\Model\Transport\Transport;

class ListTransportsResponse
{
    /** @param array<Transport> $transports */
    public function __construct(public array $transports = [], public int $totalResults = 0) {}
}
