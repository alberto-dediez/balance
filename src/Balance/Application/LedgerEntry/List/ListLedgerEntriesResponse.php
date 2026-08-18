<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\List;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;

class ListLedgerEntriesResponse
{
    /** @param array<LedgerEntry> $ledgerEntries */
    public function __construct(public array $ledgerEntries = [], public int $totalResults = 0) {}
}
