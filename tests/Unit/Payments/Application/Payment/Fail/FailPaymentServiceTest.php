<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment\Fail;

use App\Payments\Application\Payment\Fail\FailPaymentRequest;
use App\Payments\Application\Payment\Fail\FailPaymentService;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use PHPUnit\Framework\TestCase;

class FailPaymentServiceTest extends TestCase
{
    public function testFailingAPendingPaymentKeepsTheReason(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::pending();
        $payments->add($payment);

        $response = (new FailPaymentService($payments))(
            new FailPaymentRequest($payment->id(), 'card_declined'),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(PaymentStatus::Failed, $response->status);
        $this->assertSame('card_declined', $payment->failureReason());
    }

    public function testAPaidPaymentIsNeverFailed(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::paid();
        $payments->add($payment);

        $response = (new FailPaymentService($payments))(
            new FailPaymentRequest($payment->id(), 'card_declined'),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(PaymentStatus::Paid, $response->status);
        $this->assertNull($payment->failureReason());
    }

    public function testThrowsWhenThePaymentDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FailPaymentService(PaymentRepositoryMother::inMemory()))(
            new FailPaymentRequest(PaymentId::generate()),
        );
    }
}
