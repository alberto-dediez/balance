<?php

namespace App\Security\Application\User\RecoverToken;

class RecoverTokenRequest
{
    public function __construct(public string $username)
    {
        
    }
}