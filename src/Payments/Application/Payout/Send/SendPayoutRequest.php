<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Send;

use App\Payments\Domain\Model\Payout\PayoutId;

final class SendPayoutRequest
{
    public function __construct(public readonly PayoutId $payoutId) {}
}
