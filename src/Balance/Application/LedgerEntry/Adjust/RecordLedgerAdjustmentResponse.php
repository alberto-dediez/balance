<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Adjust;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;

final class RecordLedgerAdjustmentResponse
{
    public function __construct(public readonly LedgerEntryId $ledgerEntryId) {}
}
