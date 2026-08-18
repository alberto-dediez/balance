<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payout\PayoutNotPendingException;
use App\Payments\Domain\Model\Payout\PayoutReference;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Security\Domain\Model\User\UserId;
use PHPUnit\Framework\TestCase;

class PayoutTest extends TestCase
{
    public function testANewPayoutIsADraft(): void
    {
        $payout = PayoutMother::draft();

        $this->assertSame(PayoutStatus::Draft, $payout->status());
        $this->assertSame('2026/0001', $payout->number());
        $this->assertSame(280.0, $payout->payableAmount());
        $this->assertTrue($payout->isCancellable());
    }

    public function testAPayoutNeedsAPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PayoutMother::draft(amount: 0.0);
    }

    public function testAPayoutNeedsAtLeastOneLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PayoutMother::draft(linesCount: 0);
    }

    public function testItGoesFromDraftToSentAndPaid(): void
    {
        $payout = PayoutMother::draft();

        $payout->send(new PayoutReference(gateway: 'manual', externalId: 'ext-1'));

        $this->assertSame(PayoutStatus::Sent, $payout->status());
        $this->assertSame('manual', $payout->gateway());
        $this->assertSame('ext-1', $payout->externalId());

        $payout->pay('TRF-9', null, UserId::generate());

        $this->assertSame(PayoutStatus::Paid, $payout->status());
        $this->assertSame('TRF-9', $payout->reference());
        $this->assertFalse($payout->isCancellable());
    }

    public function testItCannotBeSentTwice(): void
    {
        $payout = PayoutMother::sent();

        $this->expectException(PayoutNotPendingException::class);

        $payout->send(new PayoutReference(gateway: 'manual'));
    }

    public function testAPaidPayoutCannotBePaidAgain(): void
    {
        $payout = PayoutMother::paid();

        $this->expectException(PayoutNotPendingException::class);

        $payout->pay('TRF-2', null, UserId::generate());
    }

    public function testAFailedPayoutCanBeRetriedAndCancelled(): void
    {
        $payout = PayoutMother::draft();
        $payout->fail('IBAN incorrecto');

        $this->assertSame(PayoutStatus::Failed, $payout->status());
        $this->assertTrue($payout->isCancellable());

        $payout->cancel();

        $this->assertSame(PayoutStatus::Cancelled, $payout->status());
    }

    public function testAFailedPayoutCanBeSentAgain(): void
    {
        $payout = PayoutMother::draft();
        $payout->fail('IBAN incorrecto');

        $payout->send(new PayoutReference(gateway: 'manual', externalId: 'ext-2'));

        $this->assertSame(PayoutStatus::Sent, $payout->status());
        $this->assertSame('ext-2', $payout->externalId());
        $this->assertNull($payout->failureReason());
    }

    public function testAPaidPayoutCannotBeCancelled(): void
    {
        $payout = PayoutMother::paid();

        $this->expectException(PayoutNotPendingException::class);

        $payout->cancel();
    }

    public function testASentPayoutCannotBeCancelled(): void
    {
        $payout = PayoutMother::sent();

        $this->expectException(PayoutNotPendingException::class);

        $payout->cancel();
    }

    public function testPayingAFailedPayoutClearsTheFailureReason(): void
    {
        $payout = PayoutMother::draft();
        $payout->fail('IBAN incorrecto');

        $payout->pay('TRF-3', null, UserId::generate());

        $this->assertNull($payout->failureReason());
        $this->assertSame(PayoutStatus::Paid, $payout->status());
    }
}
