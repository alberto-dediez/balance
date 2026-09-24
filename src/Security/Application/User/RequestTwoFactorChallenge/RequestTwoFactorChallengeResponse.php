<?php

declare(strict_types=1);

namespace App\Security\Application\User\RequestTwoFactorChallenge;

use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Shared\Application\CommandBus\CommandResponse;

class RequestTwoFactorChallengeResponse implements CommandResponse
{
    public function __construct(
        public TwoFactorMethod $method,
        public string $maskedDestination,
        public bool $isFallback,
        public ?\DateTimeImmutable $expiresAt,
    ) {}
}
