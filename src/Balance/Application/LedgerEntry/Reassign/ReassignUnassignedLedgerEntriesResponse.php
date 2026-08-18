<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Reassign;

final class ReassignUnassignedLedgerEntriesResponse
{
    public function __construct(
        public readonly float $reassignedAmount = 0.0,
        public readonly float $reassignedTonnes = 0.0,
    ) {}
}
