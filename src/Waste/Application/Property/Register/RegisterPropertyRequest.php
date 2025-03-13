<?php

namespace App\Waste\Application\Property\Register;

use App\Security\Domain\Model\Account\AccountId;

class RegisterPropertyRequest
{
    public function __construct(public string $name = '', public ?AccountId $accountId = null)
    {
        
    }
}