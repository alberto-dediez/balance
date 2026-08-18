<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

interface PayoutStatementLinesProvider
{
    /** @return list<PayoutStatementLine> */
    public function linesOf(PayoutId $payoutId): array;
}
