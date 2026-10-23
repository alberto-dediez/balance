<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

interface TimedCommand extends Command
{
    public function withNow(\DateTimeImmutable $now): self;
}
