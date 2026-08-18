<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payee\Payee;

interface PayoutStatementPdfGenerator
{
    /** @param list<PayoutStatementLine> $lines */
    public function generate(Payout $payout, Payee $payee, array $lines): string;
}
