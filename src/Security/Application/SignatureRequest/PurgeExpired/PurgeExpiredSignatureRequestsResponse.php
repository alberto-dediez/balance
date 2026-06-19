<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\PurgeExpired;

use App\Shared\Application\CommandBus\CommandResponse;

class PurgeExpiredSignatureRequestsResponse implements CommandResponse
{
    public function __construct(
        public int $expired,
    ) {}
}
