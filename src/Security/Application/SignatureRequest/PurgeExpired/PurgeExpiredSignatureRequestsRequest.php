<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\PurgeExpired;

use App\Shared\Application\CommandBus\Command;

class PurgeExpiredSignatureRequestsRequest implements Command
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}
}
