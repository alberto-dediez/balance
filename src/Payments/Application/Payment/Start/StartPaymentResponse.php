<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Start;

use App\Payments\Domain\Model\Payment\PaymentId;

final class StartPaymentResponse
{
    public function __construct(
        public readonly PaymentId $paymentId,
        public readonly string $externalId,
        public readonly string $redirectUrl,
    ) {}
}
