<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Prepare;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;

final class PrepareSettlementRequest
{
    public function __construct(
        public readonly LedgerBeneficiary $beneficiary,
        public readonly string $beneficiaryId,
        public readonly ?\DateTimeImmutable $until = null,
    ) {}
}
