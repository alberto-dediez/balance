<?php

namespace App\Security\Application\Account\Register;

class RegisterAccountRequest
{
    public function __construct(public string $name = '', public bool $active = false)
    {
        
    }
}