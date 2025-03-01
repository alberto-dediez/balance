<?php

namespace App\Security\Application\RegisterUser;

use App\Security\Domain\Model\Account\AccountId;

class RegisterUserRequest
{
    public function __construct(public string $username = '', public string $password = '', public ?AccountId $accountId = null)
    {
        
    }
}