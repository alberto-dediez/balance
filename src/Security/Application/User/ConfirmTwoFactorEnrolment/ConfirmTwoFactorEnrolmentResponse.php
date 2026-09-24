<?php

declare(strict_types=1);

namespace App\Security\Application\User\ConfirmTwoFactorEnrolment;

use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Shared\Application\CommandBus\CommandResponse;

class ConfirmTwoFactorEnrolmentResponse implements CommandResponse
{
    public function __construct(public TwoFactorMethod $method) {}
}
