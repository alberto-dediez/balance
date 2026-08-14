<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;

final readonly class MobilityBreakdownRow
{
    public function __construct(
        public Mobility $mobility,
        public float $km,
        public Emissions $emissions,
    ) {}
}
