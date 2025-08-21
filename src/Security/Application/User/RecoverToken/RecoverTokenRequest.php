<?php

namespace App\Security\Application\User\RecoverToken;

use App\Shared\Application\CommandBus\Command;

class RecoverTokenRequest implements Command
{
    public function __construct(public string $username)
    {
        
    }
}