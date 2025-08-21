<?php

namespace App\Security\Application\User\Register;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterUserResponse implements CommandResponse
{
    public function __construct(public UserId $userId, public string $username, public ?AccountId $accountId = null)
    {
        
    }
}