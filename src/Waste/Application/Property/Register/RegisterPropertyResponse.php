<?php

namespace App\Waste\Application\Property\Register;

use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class RegisterPropertyResponse
{
    public function __construct(public PropertyId $propertyId, public string $name, public ?AccountId $accountId = null)
    {
        
    }
}