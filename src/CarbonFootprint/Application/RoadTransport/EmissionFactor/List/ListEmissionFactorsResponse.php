<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;

class ListEmissionFactorsResponse
{
    /** @param array<RoadTransport> $roadTransports */
    public function __construct(
        public array $roadTransports,
        public int $year,
        public int $currentYear,
        public bool $canGoNext,
    ) {}
}
