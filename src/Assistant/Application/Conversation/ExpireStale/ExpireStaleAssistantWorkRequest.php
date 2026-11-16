<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\ExpireStale;

use App\Shared\Application\CommandBus\TimedCommand;

class ExpireStaleAssistantWorkRequest implements TimedCommand
{
    public function __construct(public \DateTimeImmutable $now) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
