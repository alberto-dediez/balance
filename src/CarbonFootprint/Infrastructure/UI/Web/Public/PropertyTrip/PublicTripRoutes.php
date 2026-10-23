<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;

final readonly class PublicTripRoutes
{
    public const string PROPERTY_PREFIX = 'public_carbon_footprint_property_trip';

    public const string ACCOUNT_PREFIX = 'public_carbon_footprint_trip';

    public function __construct(
        public string $prefix,
    ) {}

    public static function for(TripCalculationLink $tripCalculationLink): self
    {
        return new self($tripCalculationLink->isForProperty() ? self::PROPERTY_PREFIX : self::ACCOUNT_PREFIX);
    }

    public function name(string $suffix = ''): string
    {
        return $this->prefix . $suffix;
    }
}
