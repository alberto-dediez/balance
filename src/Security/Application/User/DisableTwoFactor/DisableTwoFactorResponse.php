<?php

declare(strict_types=1);

namespace App\Security\Application\User\DisableTwoFactor;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class DisableTwoFactorResponse implements CommandResponse
{
    public function __construct(public UserId $userId) {}
}
