<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\Emissions;

final readonly class OriginFlow
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $label,
        public int $responses,
        public int $travellers,
        public float $km,
        public Emissions $emissions,
    ) {}
}
