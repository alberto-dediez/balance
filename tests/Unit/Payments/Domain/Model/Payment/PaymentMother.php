<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payment;

use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;

class PaymentMother
{
    public static function pending(
        ?PaymentId $paymentId = null,
        string $subjectType = 'balance.project_contribution',
        ?string $subjectId = null,
        float $amount = 121.0,
    ): Payment {
        $payment = new Payment(id: $paymentId ?? PaymentId::generate());
        $payment->start(
            subjectType: $subjectType,
            subjectId: $subjectId ?? (string) PaymentId::generate(),
            description: 'Proyecto de prueba',
            amount: $amount,
            currency: 'EUR',
            customerEmail: 'guest@example.com',
            customerName: 'Guest',
            gateway: 'stripe',
            externalId: 'cs_test_' . (string) $payment->id(),
        );

        return $payment;
    }

    public static function paid(?PaymentId $paymentId = null, ?string $subjectId = null): Payment
    {
        $payment = self::pending($paymentId, subjectId: $subjectId);
        $payment->succeed('pi_test_123');

        return $payment;
    }

    public static function failed(?PaymentId $paymentId = null, ?string $subjectId = null): Payment
    {
        $payment = self::pending($paymentId, subjectId: $subjectId);
        $payment->fail('card_declined');

        return $payment;
    }

    public static function expired(?PaymentId $paymentId = null, ?string $subjectId = null): Payment
    {
        $payment = self::pending($paymentId, subjectId: $subjectId);
        $payment->expire();

        return $payment;
    }

    public static function random(): Payment
    {
        return self::pending();
    }
}
