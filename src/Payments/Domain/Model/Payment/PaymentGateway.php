<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

interface PaymentGateway
{
    public function name(): string;

    public function start(PaymentCheckout $checkout): PaymentRedirect;
}
