<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Property\Domain\Model\Property\PropertyId;

final class GetTripCalculationResultsRequest
{
    public function __construct(
        public readonly PropertyId $propertyId,
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null,
    ) {}
}
