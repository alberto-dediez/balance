<?php

namespace App\Security\Application\RegisterUser;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\Account\AccountId;

class RegisterUserResponse
{
    public function __construct(public UserId $userId, public string $username, public ?AccountId $accountId = null)
    {
        
    }
}