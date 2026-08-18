<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

use App\Shared\Application\CommandBus\Command;

final class CheckLedgerRequest implements Command
{
    public function __construct(
        public readonly bool $repair = false,
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null,
    ) {}
}
