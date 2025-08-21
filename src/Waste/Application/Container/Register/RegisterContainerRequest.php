<?php

namespace App\Waste\Application\Container\Register;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RegisterContainerRequest implements Command
{
    public function __construct(
        public PropertyId $propertyId,
        public WasteId $wasteId,
        public Purpose $purpose,
        public MeasurementMethod $measurementMethod,
        public ?string $volume,
        public WasteManagerId $wasteManagerId
    ) {
        
    }
}