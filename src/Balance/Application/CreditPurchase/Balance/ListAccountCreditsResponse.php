<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

class ListAccountCreditsResponse
{
    /** @param array<\App\Balance\Domain\Model\CreditPurchase\CreditBalance> $balances */
    public function __construct(public array $balances = []) {}
}
