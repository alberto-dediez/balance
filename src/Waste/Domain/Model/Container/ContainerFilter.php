<?php

namespace App\Waste\Domain\Model\Container;

use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class ContainerFilter
{
    use Filter;

    public function __construct(
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public ?MeasurementMethod $measurementMethod = null,
        public ?Purpose $purpose = null,
        public ?WasteManagerId $wasteManagerId = null,
        public ?AccountId $accountId = null
    ) {

    }
}