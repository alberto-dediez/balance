<?php

declare(strict_types=1);

namespace App\Security\Application\User\RequestTwoFactorEnrolment;

use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Shared\Application\CommandBus\CommandResponse;

class RequestTwoFactorEnrolmentResponse implements CommandResponse
{
    public function __construct(
        public TwoFactorMethod $method,
        public string $provisioningUri = '',
        public string $manualKey = '',
    ) {}
}
