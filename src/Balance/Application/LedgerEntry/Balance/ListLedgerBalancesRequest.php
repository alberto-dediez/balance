<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Balance;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;

final class ListLedgerBalancesRequest
{
    public function __construct(
        public readonly LedgerBeneficiary $beneficiary = LedgerBeneficiary::Account,
    ) {}
}
