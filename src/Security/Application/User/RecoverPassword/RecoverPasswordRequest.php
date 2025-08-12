<?php

namespace App\Security\Application\User\RecoverPassword;

use App\Security\Domain\Model\User\RecoverToken;

class RecoverPasswordRequest
{
    public function __construct(public RecoverToken $token, #[\SensitiveParameter] public string $password)
    {

    }
}