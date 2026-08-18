<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Settlement;

use App\Balance\Application\Settlement\Prepare\PrepareSettlementService;
use App\Balance\Application\Settlement\Settle\NothingToSettleException;
use App\Balance\Application\Settlement\Settle\SettleLedgerEntriesRequest;
use App\Balance\Application\Settlement\Settle\SettleLedgerEntriesResponse;
use App\Balance\Application\Settlement\Settle\SettleLedgerEntriesService;
use App\Balance\Application\Settlement\SettlementAuthorizer;
use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesRequest;
use App\Balance\Application\Settlement\Unsettle\UnsettleLedgerEntriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Payments\Application\Payout\Create\CreatePayoutService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Payments\Infrastructure\Persistence\InMemory\Lock\InMemoryPayoutCreationLock;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Sequence\InMemoryPayoutNumberGenerator;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SettleLedgerEntriesServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryLedgerEntryRepository $ledger;
    private InMemoryPayeeRepository $payees;
    private InMemoryPayoutRepository $payouts;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();
        $this->payees = PayeeRepositoryMother::inMemory();
        $this->payouts = PayoutRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItSettlesEveryPendingEntryOfTheBeneficiary(): void
    {
        $this->givenReadyPayee();

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));
        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 16.0));

        $response = $this->settle();

        $this->assertSame(64.0, $response->amount);
        $this->assertSame(2, $response->linesCount);
        $this->assertSame($this->numberOf(1), $response->number);
        $this->assertSame(64.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->settledAmount());
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->pendingAmount());
    }

    public function testItLeavesBioscoreAndUnassignedEntriesOut(): void
    {
        $this->givenReadyPayee();

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));
        $this->ledger->add(LedgerEntryMother::forBioscore(20.0));
        $this->ledger->add(LedgerEntryMother::unassigned(16.0));

        $response = $this->settle();

        $this->assertSame(48.0, $response->amount);
        $this->assertSame(1, $response->linesCount);
        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Unassigned, null)->settledAmount());
    }

    public function testItIgnoresAlreadySettledEntries(): void
    {
        $this->givenReadyPayee();

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));

        $this->settle();

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 10.0));

        $second = $this->settle();

        $this->assertSame(10.0, $second->amount);
        $this->assertSame($this->numberOf(2), $second->number);
    }

    public function testItRefusesToSettleWithoutPendingEntries(): void
    {
        $this->givenReadyPayee();

        $this->expectException(NothingToSettleException::class);

        $this->settle();
    }

    public function testItRefusesToSettleAPayeeThatIsNotReady(): void
    {
        $this->payees->add(PayeeMother::incomplete((string) $this->accountId));

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));

        $this->expectException(PayeeNotReadyException::class);

        $this->settle();
    }

    public function testCancellingAPayoutReleasesItsEntries(): void
    {
        $this->givenReadyPayee();

        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));

        $response = $this->settle();

        $this->assertNotNull($response->payoutId);

        $payout = $this->payouts->find($response->payoutId);

        $this->assertNotNull($payout);

        (new UnsettleLedgerEntriesService($this->ledger))(new UnsettleLedgerEntriesRequest($payout->id()));

        $this->assertSame(0.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->settledAmount());
        $this->assertSame(48.0, $this->ledger->balanceFor(LedgerBeneficiary::Account, (string) $this->accountId)->pendingAmount());
    }

    private function numberOf(int $sequence): string
    {
        return sprintf('%s/%04d', (new \DateTimeImmutable('NOW'))->format('Y'), $sequence);
    }

    private function givenReadyPayee(): void
    {
        $this->payees->add(PayeeMother::ready((string) $this->accountId, PayeeType::Account));
    }

    private function settle(): SettleLedgerEntriesResponse
    {
        return (new SettleLedgerEntriesService(
            $this->ledger,
            $this->payees,
            new PrepareSettlementService($this->ledger, new SettlementAuthorizer()),
            new CreatePayoutService(
                $this->payouts,
                $this->payees,
                new InMemoryPayoutNumberGenerator($this->payouts),
                new InMemoryPayoutCreationLock(),
                new PayoutAuthorizer(),
            ),
        ))(
            new SettleLedgerEntriesRequest(LedgerBeneficiary::Account, (string) $this->accountId),
            UserMother::adminUser(),
        );
    }
}
