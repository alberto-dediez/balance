<?php

declare(strict_types=1);

namespace App\Security\Application\User\ConfirmTwoFactorEnrolment;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class ConfirmTwoFactorEnrolmentRequest implements Command
{
    public function __construct(public UserId $userId, #[\SensitiveParameter] public string $code) {}
}
