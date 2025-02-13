<?php

namespace App\Security\Application\RegisterUser;

class RegisterUserRequest
{
    public function __construct(public string $username, public string $password)
    {
        
    }
}