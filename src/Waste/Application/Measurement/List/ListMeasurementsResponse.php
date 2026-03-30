<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\List;

use App\Waste\Domain\Model\Measurement\Measurement;

class ListMeasurementsResponse
{
    /** @param array<int, Measurement> $measurements */
    public function __construct(public array $measurements = [], public int $totalResults = 0) {}
}
