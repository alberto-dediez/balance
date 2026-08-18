<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Check;

use App\Shared\Application\CommandBus\CommandResponse;

final class CheckLedgerResponse implements CommandResponse
{
    /** @param list<LedgerFinding> $findings */
    public function __construct(
        public readonly array $findings = [],
        public readonly int $checkedContributions = 0,
    ) {}

    public function hasFindings(): bool
    {
        return [] !== $this->findings;
    }

    public function repairedCount(): int
    {
        return count(array_filter($this->findings, static fn(LedgerFinding $finding): bool => $finding->repaired));
    }
}
