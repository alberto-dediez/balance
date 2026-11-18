<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Purge;

use App\Shared\Application\CommandBus\CommandResponse;

class PurgeConversationsResponse implements CommandResponse
{
    public function __construct(public int $purged) {}
}
