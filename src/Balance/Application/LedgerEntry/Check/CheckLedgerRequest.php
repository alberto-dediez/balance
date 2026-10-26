<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

use App\Shared\Application\CommandBus\TimedCommand;

final class CheckLedgerRequest implements TimedCommand
{
    public function __construct(
        public readonly bool $repair = false,
        public readonly ?\DateTimeImmutable $from = null,
        public readonly ?\DateTimeImmutable $to = null,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        if (null === $this->to) {
            return $this;
        }

        return new self(
            repair: $this->repair,
            from: null === $this->from ? null : $now->sub($this->from->diff($this->to)),
            to: $now,
        );
    }
}
