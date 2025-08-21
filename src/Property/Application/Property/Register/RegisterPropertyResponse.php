<?php

namespace App\Property\Application\Property\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPropertyResponse implements CommandResponse
{
    public function __construct(public PropertyId $propertyId, public string $name, public ?AccountId $accountId = null, public ?PropertyInfo $info = null, public ?ExternalId $externalId = null)
    {
        
    }
}