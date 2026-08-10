<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Confirm;

use App\Payments\Domain\Model\Payment\PaymentId;

final class ConfirmPaymentRequest
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly ?string $externalPaymentId = null,
    ) {}
}
