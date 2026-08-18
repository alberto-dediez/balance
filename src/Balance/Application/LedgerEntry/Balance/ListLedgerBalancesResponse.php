<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Balance;

use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;

final class ListLedgerBalancesResponse
{
    /** @param array<string, LedgerBalance> $balances */
    public function __construct(public readonly array $balances = []) {}
}
