<?php

namespace App\Waste\Domain\Model\Container;

use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyId;

class ContainerFilter
{
    use Filter;

    public function __construct(
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public ?MeasurementMethod $measurementMethod = null,
        public ?Purpose $purpose = null,
        public ?Account $account = null
    ) {

    }
}