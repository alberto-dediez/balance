<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Shared\Application\CommandBus\Command;

class ExpirePendingContributionsRequest implements Command
{
    public function __construct(public readonly \DateTimeImmutable $now = new \DateTimeImmutable('NOW')) {}
}
