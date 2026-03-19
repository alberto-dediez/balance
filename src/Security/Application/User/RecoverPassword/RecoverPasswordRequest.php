<?php

declare(strict_types=1);

namespace App\Security\Application\User\RecoverPassword;

use App\Security\Domain\Model\User\RecoverToken;
use App\Shared\Application\CommandBus\Command;

class RecoverPasswordRequest implements Command
{
    public function __construct(public RecoverToken $token, #[\SensitiveParameter] public string $password) {}
}
