<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\Emissions;

final readonly class TripEmissions
{
    public function __construct(
        public Emissions $mobility,
        public Emissions $overnight,
        public bool $overnightStayCatalogMissing = false,
    ) {}

    public function total(): Emissions
    {
        return $this->mobility->add($this->overnight);
    }
}
