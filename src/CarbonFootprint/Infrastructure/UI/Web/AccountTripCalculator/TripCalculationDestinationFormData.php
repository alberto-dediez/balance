<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\AccountTripCalculator;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;

final class TripCalculationDestinationFormData
{
    /**
     * @param list<string> $countryIds
     * @param list<string> $propertyIds
     */
    public function __construct(
        public TripDestinationMode $mode = TripDestinationMode::Free,
        public TripDestinationPlaceKind $placeKind = TripDestinationPlaceKind::City,
        public array $countryIds = [],
        public array $propertyIds = [],
        public ?string $regionReference = null,
        public ?string $regionLabel = null,
    ) {}
}
