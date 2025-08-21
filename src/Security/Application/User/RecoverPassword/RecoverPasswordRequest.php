<?php

namespace App\Security\Application\User\RecoverPassword;

use App\Shared\Application\CommandBus\Command;
use App\Security\Domain\Model\User\RecoverToken;

class RecoverPasswordRequest implements Command
{
    public function __construct(public RecoverToken $token, #[\SensitiveParameter] public string $password)
    {

    }
}