<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Unsettle;

use App\Payments\Domain\Model\Payout\PayoutId;

final class UnsettleLedgerEntriesRequest
{
    public function __construct(public readonly PayoutId $payoutId) {}
}
