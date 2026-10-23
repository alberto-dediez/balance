<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetAccountResults;

final class GetAccountTripCalculatorResultsRequest
{
    public function __construct(
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null,
    ) {}
}
