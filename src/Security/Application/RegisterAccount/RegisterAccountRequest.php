<?php

namespace App\Security\Application\RegisterAccount;

class RegisterAccountRequest
{
    public function __construct(public string $name = '', public bool $active = false)
    {
        
    }
}