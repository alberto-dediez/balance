<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ExpireStale;

use App\Shared\Application\CommandBus\CommandResponse;

class ExpireStaleAssistantWorkResponse implements CommandResponse
{
    public function __construct(public int $failedMessages, public int $interruptedPlans, public int $expiredPlans) {}
}
