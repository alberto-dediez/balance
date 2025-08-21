<?php

namespace App\Waste\Application\Container\Register;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class RegisterContainerResponse implements CommandResponse
{
    public function __construct(
        public ContainerId $containerId, 
        public PropertyId $propertyId,
        public WasteId $wasteId,
        public Purpose $purpose,
        public MeasurementMethod $measurementMethod,
        public ?string $volume,
        public WasteManagerId $wasteManagerId,
        public QrCode $qrCode
    ) {
        
    }
}