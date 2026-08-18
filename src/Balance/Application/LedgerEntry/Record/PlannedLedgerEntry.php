<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Record;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;

final class PlannedLedgerEntry
{
    public function __construct(
        public readonly LedgerEntryType $type,
        public readonly LedgerBeneficiary $beneficiary,
        public readonly ?string $beneficiaryId,
        public float $amount,
        public readonly ?float $tonnes = null,
        public readonly ?CreditPurchaseId $creditPurchaseId = null,
    ) {}
}
