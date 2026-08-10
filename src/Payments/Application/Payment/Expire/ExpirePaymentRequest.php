<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Expire;

use App\Payments\Domain\Model\Payment\PaymentId;

final class ExpirePaymentRequest
{
    public function __construct(public readonly PaymentId $paymentId) {}
}
