<?php

namespace App\Security\Application\Account\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterAccountRequest implements Command
{
    public function __construct(public string $name = '', public bool $active = false)
    {
        
    }
}