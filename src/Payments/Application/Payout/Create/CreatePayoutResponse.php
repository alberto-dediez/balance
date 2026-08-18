<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Create;

use App\Payments\Domain\Model\Payout\PayoutId;

final class CreatePayoutResponse
{
    public function __construct(
        public readonly PayoutId $payoutId,
        public readonly string $number,
    ) {}
}
