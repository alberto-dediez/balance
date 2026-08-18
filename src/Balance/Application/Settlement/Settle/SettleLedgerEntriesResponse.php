<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Settle;

use App\Payments\Domain\Model\Payout\PayoutId;

final class SettleLedgerEntriesResponse
{
    public function __construct(
        public readonly ?PayoutId $payoutId = null,
        public readonly ?string $number = null,
        public readonly float $amount = 0.0,
        public readonly int $linesCount = 0,
    ) {}
}
