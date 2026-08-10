<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment\Confirm;

use App\Payments\Application\Payment\Confirm\ConfirmPaymentRequest;
use App\Payments\Application\Payment\Confirm\ConfirmPaymentService;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use PHPUnit\Framework\TestCase;

class ConfirmPaymentServiceTest extends TestCase
{
    public function testConfirmingAPendingPaymentMarksItAsPaid(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::pending();
        $payments->add($payment);

        $response = (new ConfirmPaymentService($payments))(
            new ConfirmPaymentRequest($payment->id(), 'pi_123'),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(PaymentStatus::Paid, $response->status);
        $this->assertSame('pi_123', $payment->externalPaymentId());
    }

    public function testConfirmingTwiceIsANoOp(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::pending();
        $payments->add($payment);

        $sut = new ConfirmPaymentService($payments);

        $sut(new ConfirmPaymentRequest($payment->id(), 'pi_123'));
        $response = $sut(new ConfirmPaymentRequest($payment->id(), 'pi_456'));

        $this->assertFalse($response->changed);
        $this->assertSame(PaymentStatus::Paid, $response->status);
        $this->assertSame('pi_123', $payment->externalPaymentId());
    }

    public function testAFailedPaymentIsNotConfirmed(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::failed();
        $payments->add($payment);

        $response = (new ConfirmPaymentService($payments))(
            new ConfirmPaymentRequest($payment->id()),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(PaymentStatus::Failed, $response->status);
    }

    public function testThrowsWhenThePaymentDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ConfirmPaymentService(PaymentRepositoryMother::inMemory()))(
            new ConfirmPaymentRequest(PaymentId::generate()),
        );
    }
}
