<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Payments\Domain\Model\Payment\PaymentCheckout;
use App\Payments\Domain\Model\Payment\PaymentGateway;
use App\Payments\Domain\Model\Payment\PaymentGatewayException;
use App\Payments\Domain\Model\Payment\PaymentRedirect;

class FailingPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function start(PaymentCheckout $checkout): PaymentRedirect
    {
        throw new PaymentGatewayException('The gateway is unreachable.');
    }
}
