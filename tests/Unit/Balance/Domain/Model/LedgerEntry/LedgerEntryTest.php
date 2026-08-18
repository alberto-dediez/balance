<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRecorded;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntrySettled;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\LedgerEntry\UnsettleableLedgerEntryException;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class LedgerEntryTest extends TestCase
{
    public function testItRecordsAnEntry(): void
    {
        $accountId = AccountId::generate();

        $ledgerEntry = LedgerEntryMother::forAccount($accountId, 48.0);

        $this->assertSame(48.0, $ledgerEntry->amount());
        $this->assertSame(LedgerBeneficiary::Account, $ledgerEntry->beneficiary());
        $this->assertSame((string) $accountId, $ledgerEntry->beneficiaryId());
        $this->assertContainsOnlyInstancesOf(LedgerEntryRecorded::class, $ledgerEntry->recordedEvents());
    }

    public function testItRequiresABeneficiaryIdForSettleableBeneficiaries(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LedgerEntry(id: LedgerEntryId::generate()))->recordEntry(
            type: LedgerEntryType::ContributionNet,
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: null,
            amount: 10.0,
            currency: 'EUR',
        );
    }

    public function testItRequiresAConceptForAdjustments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LedgerEntry(id: LedgerEntryId::generate()))->recordEntry(
            type: LedgerEntryType::Adjustment,
            beneficiary: LedgerBeneficiary::Bioscore,
            beneficiaryId: null,
            amount: -10.0,
            currency: 'EUR',
        );
    }

    public function testItSettlesASettleableEntry(): void
    {
        $ledgerEntry = LedgerEntryMother::forAccount();
        $payoutId = PayoutId::generate();

        $ledgerEntry->settle($payoutId);

        $this->assertTrue($ledgerEntry->isSettled());
        $this->assertFalse($ledgerEntry->isSettleable());
        $this->assertSame((string) $payoutId, (string) $ledgerEntry->payoutId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ledgerEntry->settledAt());
    }

    public function testItRefusesToSettleTwice(): void
    {
        $ledgerEntry = LedgerEntryMother::forAccount();
        $ledgerEntry->settle(PayoutId::generate());

        $this->expectException(UnsettleableLedgerEntryException::class);

        $ledgerEntry->settle(PayoutId::generate());
    }

    /** @return iterable<string, array{LedgerEntry}> */
    public static function unsettleableEntries(): iterable
    {
        yield 'bioscore' => [LedgerEntryMother::forBioscore()];
        yield 'unassigned' => [LedgerEntryMother::unassigned()];
        yield 'zero amount' => [LedgerEntryMother::forAccount(amount: 0.0)];
    }

    /** @dataProvider unsettleableEntries */
    public function testItRefusesToSettleUnsettleableEntries(LedgerEntry $ledgerEntry): void
    {
        $this->assertFalse($ledgerEntry->isSettleable());

        $this->expectException(UnsettleableLedgerEntryException::class);

        $ledgerEntry->settle(PayoutId::generate());
    }

    public function testItUnsettlesASettledEntry(): void
    {
        $ledgerEntry = LedgerEntryMother::forAccount();
        $ledgerEntry->settle(PayoutId::generate());

        $ledgerEntry->unsettle();

        $this->assertFalse($ledgerEntry->isSettled());
        $this->assertTrue($ledgerEntry->isSettleable());
        $this->assertNull($ledgerEntry->settledAt());
    }

    public function testItIgnoresUnsettlingAPendingEntry(): void
    {
        $ledgerEntry = LedgerEntryMother::forAccount();

        $ledgerEntry->unsettle();

        $events = array_filter(
            $ledgerEntry->recordedEvents(),
            static fn(object $event): bool => $event instanceof LedgerEntrySettled,
        );

        $this->assertSame([], $events);
    }
}
