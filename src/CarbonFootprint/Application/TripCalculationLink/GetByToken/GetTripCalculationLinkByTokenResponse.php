<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\GetByToken;

use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;

final class GetTripCalculationLinkByTokenResponse
{
    /** @param array<Mobility> $mobilities */
    public function __construct(
        public readonly TripCalculationLink $tripCalculationLink,
        public readonly ?Property $property,
        public readonly array $mobilities,
        public readonly bool $geoEnabled = false,
        public readonly int $maxLegs = 16,
        public readonly string $ownerName = '',
        public readonly bool $asksForDestination = false,
    ) {}
}
