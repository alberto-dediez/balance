<?php

declare(strict_types=1);

namespace App\Security\Application\User\ChangePassword;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class ChangePasswordRequest implements Command
{
    public function __construct(public UserId $userId, #[\SensitiveParameter] public string $password) {}
}
