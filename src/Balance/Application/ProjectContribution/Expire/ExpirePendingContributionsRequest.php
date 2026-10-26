<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Shared\Application\CommandBus\TimedCommand;

class ExpirePendingContributionsRequest implements TimedCommand
{
    public function __construct(public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW')) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
