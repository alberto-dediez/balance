<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Cancel;

use App\Payments\Domain\Model\Payout\PayoutId;

final class CancelPayoutRequest
{
    public function __construct(public readonly PayoutId $payoutId) {}
}
