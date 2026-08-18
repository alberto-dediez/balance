<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Pay;

use App\Payments\Domain\Model\Payout\PayoutStatus;

final class MarkPayoutPaidResponse
{
    public function __construct(public readonly PayoutStatus $status) {}
}
