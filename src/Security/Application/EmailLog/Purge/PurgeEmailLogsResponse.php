<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog\Purge;

use App\Shared\Application\CommandBus\CommandResponse;

class PurgeEmailLogsResponse implements CommandResponse
{
    public function __construct(
        public int $removed,
    ) {}
}
