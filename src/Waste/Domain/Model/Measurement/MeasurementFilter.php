<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;

class MeasurementFilter
{
    use Filter;

    public function __construct(
        public ?ContainerId $containerId = null,
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public ?MeasurementMethod $measurementMethod = null,
        public ?Purpose $purpose = null,
        public ?Account $account = null,
        public ?UserId $userId = null
    ) {

    }
}