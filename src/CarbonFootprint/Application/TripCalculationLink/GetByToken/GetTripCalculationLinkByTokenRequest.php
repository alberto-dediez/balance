<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\GetByToken;

final class GetTripCalculationLinkByTokenRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
