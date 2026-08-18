<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Fail;

use App\Payments\Domain\Model\Payout\PayoutId;

class FailPayoutRequest
{
    public function __construct(
        public PayoutId $payoutId,
        public string $reason = '',
    ) {}
}
