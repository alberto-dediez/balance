<?php

namespace App\Waste\Application\Property\Register;

use App\Security\Domain\Model\Account\Account;

class RegisterPropertyRequest
{
    public function __construct(public string $name = '', public ?Account $account = null)
    {
        
    }
}