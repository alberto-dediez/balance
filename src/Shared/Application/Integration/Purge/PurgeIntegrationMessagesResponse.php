<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration\Purge;

use App\Shared\Application\CommandBus\CommandResponse;

class PurgeIntegrationMessagesResponse implements CommandResponse
{
    public function __construct(
        public int $outboxRemoved,
        public int $inboxRemoved,
    ) {}
}
