<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class LedgerEntryRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAddLedgerEntryToRepository(): void
    {
        $repository = $this->createSUT();

        $ledgerEntry = LedgerEntryMother::forAccount();

        $this->assertNull($repository->find($ledgerEntry->id()));

        $repository->add($ledgerEntry);

        $recovered = $repository->find($ledgerEntry->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($ledgerEntry->id()->equals($recovered->id()));
    }

    public function testFindByFilterByBeneficiary(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forBioscore());

        $result = $repository->findByFilter(new LedgerEntryFilter(
            beneficiary: LedgerBeneficiary::Account,
            limit: 0,
        ));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByBeneficiaryId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(LedgerEntryMother::forAccount($accountId, 48.0));
        $repository->add(LedgerEntryMother::forAccount($accountId, 32.0));
        $repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 10.0));

        $result = $repository->findByFilter(new LedgerEntryFilter(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) $accountId,
            limit: 0,
        ));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByType(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));

        $result = $repository->findByFilter(new LedgerEntryFilter(
            type: LedgerEntryType::ManagementFee,
            limit: 0,
        ));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByTypes(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));
        $repository->add(LedgerEntryMother::forBioscore(4.0, LedgerEntryType::Vat));

        $result = $repository->findByFilter(new LedgerEntryFilter(
            types: [LedgerEntryType::ManagementFee, LedgerEntryType::Vat],
            limit: 0,
        ));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByProjectContributionId(): void
    {
        $repository = $this->createSUT();

        $contributionId = ProjectContributionId::generate();

        $repository->add(LedgerEntryMother::recorded(projectContributionId: $contributionId));
        $repository->add(LedgerEntryMother::recorded(projectContributionId: $contributionId));
        $repository->add(LedgerEntryMother::forAccount());

        $result = $repository->findByFilter(new LedgerEntryFilter(
            projectContributionId: $contributionId,
            limit: 0,
        ));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterBySettledStatus(): void
    {
        $repository = $this->createSUT();

        $settled = LedgerEntryMother::forAccount();
        $settled->settle(PayoutId::generate());

        $repository->add($settled);
        $repository->add(LedgerEntryMother::forAccount());

        $result = $repository->findByFilter(new LedgerEntryFilter(
            beneficiary: LedgerBeneficiary::Account,
            settled: true,
            limit: 0,
        ));

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->id()->equals($settled->id()));
    }

    public function testFindByFilterByDateRange(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::recorded(occurredAt: new \DateTimeImmutable('2026-01-15')));
        $repository->add(LedgerEntryMother::recorded(occurredAt: new \DateTimeImmutable('2026-02-15')));

        $result = $repository->findByFilter(new LedgerEntryFilter(
            from: new \DateTimeImmutable('2026-01-01'),
            to: new \DateTimeImmutable('2026-01-31'),
            limit: 0,
        ));

        $this->assertCount(1, $result);
    }

    public function testTotalResultsByFilterCountsAllMatches(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forAccount());
        $repository->add(LedgerEntryMother::forBioscore());

        $total = $repository->totalResultsByFilter(new LedgerEntryFilter(
            beneficiary: LedgerBeneficiary::Account,
            limit: 1,
        ));

        $this->assertSame(2, $total);
    }

    public function testBalanceForSumsAccruedAndSettledForABeneficiary(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $first = LedgerEntryMother::forAccount($accountId, 48.0);
        $first->settle(PayoutId::generate());
        $second = LedgerEntryMother::forAccount($accountId, 32.0);

        $repository->add($first);
        $repository->add($second);

        $balance = $repository->balanceFor(LedgerBeneficiary::Account, (string) $accountId);

        $this->assertSame(80.0, $balance->accruedAmount());
        $this->assertSame(48.0, $balance->settledAmount());
        $this->assertSame(32.0, $balance->pendingAmount());
        $this->assertSame(2, $balance->entriesCount());
    }

    public function testBalanceForUnknownBeneficiaryIdReturnsZero(): void
    {
        $repository = $this->createSUT();

        $balance = $repository->balanceFor(LedgerBeneficiary::Account, (string) AccountId::generate());

        $this->assertSame(0.0, $balance->accruedAmount());
        $this->assertSame(0, $balance->entriesCount());
    }

    public function testBalancesByBeneficiaryGroupsByBeneficiaryId(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));
        $repository->add(LedgerEntryMother::forBioscore());

        $balances = $repository->balancesByBeneficiary(LedgerBeneficiary::Account);

        $this->assertCount(2, $balances);
    }

    public function testTotalsByTypeSumsAmountsByType(): void
    {
        $repository = $this->createSUT();

        $repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));
        $repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));

        $totals = $repository->totalsByType(new LedgerEntryFilter(limit: 0));

        $this->assertSame(80.0, $totals[LedgerEntryType::ContributionNet->value]);
        $this->assertSame(20.0, $totals[LedgerEntryType::ManagementFee->value]);
    }

    public function testFindSettleableReturnsOnlyUnsettledSettleableEntries(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $pending = LedgerEntryMother::forAccount($accountId, 48.0);
        $settled = LedgerEntryMother::forAccount($accountId, 32.0);
        $settled->settle(PayoutId::generate());

        $repository->add($pending);
        $repository->add($settled);
        $repository->add(LedgerEntryMother::forBioscore());

        $settleable = $repository->findSettleable(LedgerBeneficiary::Account, (string) $accountId, null);

        $this->assertCount(1, $settleable);
        $this->assertTrue($settleable[0]->id()->equals($pending->id()));
    }

    public function testFindByPayoutReturnsEntriesSettledWithThatPayout(): void
    {
        $repository = $this->createSUT();

        $payoutId = PayoutId::generate();

        $settled = LedgerEntryMother::forAccount();
        $settled->settle($payoutId);

        $repository->add($settled);
        $repository->add(LedgerEntryMother::forAccount());

        $result = $repository->findByPayout($payoutId);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->id()->equals($settled->id()));
    }

    public function testRecordedAmountForSumsEntriesOfAContribution(): void
    {
        $repository = $this->createSUT();

        $contributionId = ProjectContributionId::generate();

        $repository->add(LedgerEntryMother::recorded(amount: 48.0, projectContributionId: $contributionId));
        $repository->add(LedgerEntryMother::recorded(amount: 32.0, projectContributionId: $contributionId));
        $repository->add(LedgerEntryMother::forAccount());

        $this->assertSame(80.0, $repository->recordedAmountFor($contributionId));
    }

    abstract protected function createSUT(): LedgerEntryRepository;
}
