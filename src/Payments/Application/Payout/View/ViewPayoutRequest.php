<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\View;

use App\Payments\Domain\Model\Payout\PayoutId;

final class ViewPayoutRequest
{
    public function __construct(public readonly PayoutId $payoutId) {}
}
