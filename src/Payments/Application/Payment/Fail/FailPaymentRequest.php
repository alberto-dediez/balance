<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Fail;

use App\Payments\Domain\Model\Payment\PaymentId;

final class FailPaymentRequest
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly string $reason = '',
    ) {}
}
