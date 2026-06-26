<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\List;

class ListRoadTransportsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport> $roadTransports */
    public function __construct(public array $roadTransports = [], public int $totalResults = 0) {}
}
