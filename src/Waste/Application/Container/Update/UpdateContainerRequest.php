<?php

namespace App\Waste\Application\Container\Update;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Shared\Application\CommandBus\Command;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class UpdateContainerRequest implements Command
{
    public function __construct(
        public ContainerId $containerId, 
        public WasteId $wasteId,
        public Purpose $purpose,
        public MeasurementMethod $measurementMethod,
        public ?string $volume,
        public WasteManagerId $wasteManagerId
    ) {

    }
}