<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\PurgeExpired;

use App\Shared\Application\CommandBus\TimedCommand;

class PurgeExpiredSignatureRequestsRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
