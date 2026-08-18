<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Record;

final class RecordContributionLedgerEntriesResponse
{
    public function __construct(
        public readonly int $entriesCount = 0,
        public readonly float $recordedAmount = 0.0,
        public readonly float $unassignedAmount = 0.0,
    ) {}
}
