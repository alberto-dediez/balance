<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Cancel;

use App\Payments\Domain\Model\Payout\PayoutStatus;

final class CancelPayoutResponse
{
    public function __construct(public readonly PayoutStatus $status) {}
}
