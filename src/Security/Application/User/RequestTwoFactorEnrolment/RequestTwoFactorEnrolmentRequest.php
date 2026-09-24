<?php

declare(strict_types=1);

namespace App\Security\Application\User\RequestTwoFactorEnrolment;

use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class RequestTwoFactorEnrolmentRequest implements Command
{
    public function __construct(public UserId $userId, public TwoFactorMethod $method) {}
}
