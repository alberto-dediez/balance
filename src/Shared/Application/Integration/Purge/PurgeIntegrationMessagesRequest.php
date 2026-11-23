<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration\Purge;

use App\Shared\Application\CommandBus\TimedCommand;

class PurgeIntegrationMessagesRequest implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
