<?php

namespace App\Waste\Application\Property\Register;

use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\Account;

class RegisterPropertyResponse
{
    public function __construct(public PropertyId $propertyId, public string $name, public ?Account $account = null)
    {
        
    }
}