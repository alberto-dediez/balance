<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Prepare;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;

final class PrepareSettlementResponse
{
    /** @param list<LedgerEntry> $entries */
    public function __construct(
        public readonly array $entries = [],
        public readonly float $amount = 0.0,
        public readonly ?\DateTimeImmutable $periodFrom = null,
        public readonly ?\DateTimeImmutable $periodTo = null,
    ) {}

    public function isEmpty(): bool
    {
        return [] === $this->entries;
    }
}
