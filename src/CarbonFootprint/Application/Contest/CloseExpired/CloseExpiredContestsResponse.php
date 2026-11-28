<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\CloseExpired;

use App\Shared\Application\CommandBus\CommandResponse;

class CloseExpiredContestsResponse implements CommandResponse
{
    public function __construct(
        public int $closed = 0,
    ) {}
}
