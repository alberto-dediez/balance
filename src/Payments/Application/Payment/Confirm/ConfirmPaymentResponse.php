<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Confirm;

use App\Payments\Domain\Model\Payment\PaymentStatus;

final class ConfirmPaymentResponse
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly bool $changed,
    ) {}
}
