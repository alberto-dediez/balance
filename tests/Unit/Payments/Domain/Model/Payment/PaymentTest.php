<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payment;

use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testStartingRecordsTheGatewayData(): void
    {
        $payment = PaymentMother::pending();

        $this->assertSame(PaymentStatus::Pending, $payment->status());
        $this->assertSame('stripe', $payment->gateway());
        $this->assertNotNull($payment->externalId());
        $this->assertCount(1, $payment->recordedEvents());
    }

    public function testCannotStartWithANonPositiveAmount(): void
    {
        $payment = new Payment(id: PaymentId::generate());

        $this->expectException(\InvalidArgumentException::class);

        $payment->start('subject', 'id', 'desc', 0.0, 'EUR', 'a@b.c', 'A', 'stripe', 'cs_1');
    }

    public function testSucceedingMarksThePaymentAsPaid(): void
    {
        $payment = PaymentMother::pending();
        $payment->succeed('pi_123');

        $this->assertSame(PaymentStatus::Paid, $payment->status());
        $this->assertSame('pi_123', $payment->externalPaymentId());
        $this->assertNotNull($payment->paidAt());
        $this->assertTrue($payment->isPaid());
    }

    public function testSucceedingTwiceRecordsASingleEvent(): void
    {
        $payment = PaymentMother::pending();
        $payment->succeed('pi_123');
        $payment->succeed('pi_456');

        $this->assertCount(2, $payment->recordedEvents());
        $this->assertSame('pi_123', $payment->externalPaymentId());
    }

    public function testAPaidPaymentIsNeitherFailedNorExpiredAfterwards(): void
    {
        $payment = PaymentMother::paid();
        $payment->fail('card_declined');
        $payment->expire();

        $this->assertSame(PaymentStatus::Paid, $payment->status());
        $this->assertNull($payment->failureReason());
    }

    public function testFailingKeepsTheReason(): void
    {
        $payment = PaymentMother::pending();
        $payment->fail('card_declined');

        $this->assertSame(PaymentStatus::Failed, $payment->status());
        $this->assertSame('card_declined', $payment->failureReason());
        $this->assertNotNull($payment->resolvedAt());
    }

    public function testExpiringResolvesThePayment(): void
    {
        $payment = PaymentMother::pending();
        $payment->expire();
        $payment->expire();

        $this->assertSame(PaymentStatus::Expired, $payment->status());
        $this->assertCount(2, $payment->recordedEvents());
    }
}
