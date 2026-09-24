<?php

declare(strict_types=1);

namespace App\Security\Application\User\DisableTwoFactor;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class DisableTwoFactorRequest implements Command
{
    public function __construct(public UserId $userId) {}
}
