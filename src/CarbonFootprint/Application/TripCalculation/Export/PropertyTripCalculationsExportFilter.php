<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\Export;

use App\Property\Domain\Model\Property\PropertyId;

class PropertyTripCalculationsExportFilter
{
    public function __construct(
        public ?PropertyId $propertyId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {}
}
