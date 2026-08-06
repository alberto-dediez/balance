<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\Register;

use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class RegisterTripCalculationLinkRequest implements Command
{
    public function __construct(
        public PropertyId $propertyId,
    ) {}
}
