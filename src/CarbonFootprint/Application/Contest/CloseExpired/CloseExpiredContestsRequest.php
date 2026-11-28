<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\CloseExpired;

use App\Shared\Application\CommandBus\TimedCommand;

class CloseExpiredContestsRequest implements TimedCommand
{
    public function __construct(
        public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW'),
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
