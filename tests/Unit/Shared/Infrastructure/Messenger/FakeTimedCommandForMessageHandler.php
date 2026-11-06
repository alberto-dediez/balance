<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Shared\Application\CommandBus\TimedCommand;

class FakeTimedCommandForMessageHandler implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
