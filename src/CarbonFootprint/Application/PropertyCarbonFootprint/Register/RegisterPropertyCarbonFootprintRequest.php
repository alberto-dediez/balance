<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\Register;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterPropertyCarbonFootprintRequest implements Command
{
    public function __construct(
        public ?PropertyId $propertyId = null,
        public int $year = 0,
        public ?AccountId $accountId = null,
        public ?EmissionFactorGroupId $emissionFactorGroupId = null,
    ) {}
}
