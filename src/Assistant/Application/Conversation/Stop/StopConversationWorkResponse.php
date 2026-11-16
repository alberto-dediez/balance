<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Stop;

use App\Shared\Application\CommandBus\CommandResponse;

class StopConversationWorkResponse implements CommandResponse
{
    public function __construct(public bool $stopped) {}
}
