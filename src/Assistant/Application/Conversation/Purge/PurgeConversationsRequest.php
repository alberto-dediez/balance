<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\Purge;

use App\Shared\Application\CommandBus\TimedCommand;

class PurgeConversationsRequest implements TimedCommand
{
    public function __construct(public \DateTimeImmutable $now) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
