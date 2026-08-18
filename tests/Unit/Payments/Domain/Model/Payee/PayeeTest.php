<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payee;

use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Shared\Domain\Model\Address;
use PHPUnit\Framework\TestCase;

class PayeeTest extends TestCase
{
    public function testAPayeeWithFullFiscalAndBankDataIsReady(): void
    {
        $this->assertSame(PayeeStatus::Ready, PayeeMother::ready()->status());
    }

    public function testAPayeeMissingBankDataIsIncomplete(): void
    {
        $this->assertSame(PayeeStatus::Incomplete, PayeeMother::incomplete()->status());
    }

    public function testCompletingTheBankDataMakesThePayeeReady(): void
    {
        $payee = PayeeMother::incomplete();

        $payee->update(
            legalName: 'Hotel X SL',
            taxId: 'B12345678',
            address: new Address(),
            iban: 'ES9121000418450200051332',
            bic: null,
            holderName: 'Hotel X SL',
            notes: null,
        );

        $this->assertSame(PayeeStatus::Ready, $payee->status());
    }

    public function testABlockedPayeeStaysBlockedAfterAnUpdate(): void
    {
        $payee = PayeeMother::blocked();

        $payee->update(
            legalName: 'Hotel X SL',
            taxId: 'B12345678',
            address: new Address(),
            iban: 'ES9121000418450200051332',
            bic: null,
            holderName: 'Hotel X SL',
            notes: null,
        );

        $this->assertTrue($payee->isBlocked());
    }

    public function testUnblockingRecalculatesTheStatus(): void
    {
        $payee = PayeeMother::blocked();

        $payee->unblock();

        $this->assertSame(PayeeStatus::Ready, $payee->status());
    }

    public function testTheIbanIsMaskedExceptForItsLastFourDigits(): void
    {
        $payee = PayeeMother::ready();

        $this->assertSame('ES' . str_repeat('*', 18) . '1332', $payee->maskedIban());
    }

    public function testTheMaskedIbanIsNullWithoutBankData(): void
    {
        $this->assertNull(PayeeMother::incomplete()->maskedIban());
    }

    public function testALegalNameIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PayeeMother::from(legalName: '');
    }
}
