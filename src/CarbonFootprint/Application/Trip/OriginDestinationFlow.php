<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\Emissions;

final readonly class OriginDestinationFlow
{
    public function __construct(
        public float $originLatitude,
        public float $originLongitude,
        public string $originLabel,
        public float $destinationLatitude,
        public float $destinationLongitude,
        public string $destinationLabel,
        public int $responses,
        public int $travellers,
        public float $km,
        public Emissions $emissions,
    ) {}
}
