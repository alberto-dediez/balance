<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\List;

class ListCreditPurchasesResponse
{
    /** @param array<\App\Balance\Domain\Model\CreditPurchase\CreditPurchase> $creditPurchases */
    public function __construct(public array $creditPurchases = [], public int $totalResults = 0) {}
}
