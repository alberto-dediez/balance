<?php

namespace App\Security\Application\User\Register;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\Account\AccountId;

class RegisterUserRequest implements Command
{
    public function __construct(public string $username = '', #[\SensitiveParameter] public string $password = '', public ?AccountId $accountId = null)
    {
        
    }
}