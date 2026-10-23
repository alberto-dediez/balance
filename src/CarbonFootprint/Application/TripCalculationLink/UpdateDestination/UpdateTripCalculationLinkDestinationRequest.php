<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdateTripCalculationLinkDestinationRequest implements Command
{
    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    public function __construct(
        public TripCalculationLinkId $tripCalculationLinkId,
        public TripDestinationMode $mode,
        public TripDestinationPlaceKind $placeKind,
        public array $countryIds = [],
        public array $propertyIds = [],
        public ?string $regionReference = null,
    ) {}
}
