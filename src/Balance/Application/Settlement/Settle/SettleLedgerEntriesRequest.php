<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Settle;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;

final class SettleLedgerEntriesRequest
{
    public function __construct(
        public readonly LedgerBeneficiary $beneficiary,
        public readonly string $beneficiaryId,
        public readonly ?\DateTimeImmutable $until = null,
        public readonly ?string $notes = null,
    ) {}
}
