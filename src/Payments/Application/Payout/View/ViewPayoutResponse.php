<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\View;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payout\Payout;

final class ViewPayoutResponse
{
    public function __construct(
        public readonly Payout $payout,
        public readonly ?Payee $payee = null,
    ) {}
}
