<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Fail;

use App\Payments\Domain\Model\Payout\PayoutStatus;

final class FailPayoutResponse
{
    public function __construct(public readonly PayoutStatus $status) {}
}
