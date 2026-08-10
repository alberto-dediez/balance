<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment\Expire;

use App\Payments\Application\Payment\Expire\ExpirePaymentRequest;
use App\Payments\Application\Payment\Expire\ExpirePaymentService;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use PHPUnit\Framework\TestCase;

class ExpirePaymentServiceTest extends TestCase
{
    public function testExpiringAPendingPayment(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::pending();
        $payments->add($payment);

        $response = (new ExpirePaymentService($payments))(new ExpirePaymentRequest($payment->id()));

        $this->assertTrue($response->changed);
        $this->assertSame(PaymentStatus::Expired, $response->status);
        $this->assertNotNull($payment->resolvedAt());
    }

    public function testAPaidPaymentIsNeverExpired(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::paid();
        $payments->add($payment);

        $response = (new ExpirePaymentService($payments))(new ExpirePaymentRequest($payment->id()));

        $this->assertFalse($response->changed);
        $this->assertSame(PaymentStatus::Paid, $response->status);
    }

    public function testThrowsWhenThePaymentDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ExpirePaymentService(PaymentRepositoryMother::inMemory()))(
            new ExpirePaymentRequest(PaymentId::generate()),
        );
    }
}
