<?php

namespace App\Security\Application\Account\Register;

use App\Security\Domain\Model\Account\AccountId;

class RegisterAccountResponse
{
    public function __construct(public AccountId $accountId, public string $name, public bool $active)
    {
        
    }
}