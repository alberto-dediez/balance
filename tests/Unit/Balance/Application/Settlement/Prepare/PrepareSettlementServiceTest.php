<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Settlement\Prepare;

use App\Balance\Application\Settlement\Prepare\PrepareSettlementRequest;
use App\Balance\Application\Settlement\Prepare\PrepareSettlementResponse;
use App\Balance\Application\Settlement\Prepare\PrepareSettlementService;
use App\Balance\Application\Settlement\SettlementAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PrepareSettlementServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryLedgerEntryRepository $ledger;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->ledger = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItGathersEverySettleableEntryForTheBeneficiary(): void
    {
        $this->ledger->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) $this->accountId,
            amount: 48.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));
        $this->ledger->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) $this->accountId,
            amount: 16.0,
            occurredAt: new \DateTimeImmutable('2026-02-15'),
        ));

        $response = $this->prepare();

        $this->assertFalse($response->isEmpty());
        $this->assertCount(2, $response->entries);
        $this->assertSame(64.0, $response->amount);
        $this->assertEquals(new \DateTimeImmutable('2026-01-10'), $response->periodFrom);
        $this->assertEquals(new \DateTimeImmutable('2026-02-15'), $response->periodTo);
    }

    public function testItReturnsAnEmptyResponseWhenThereIsNothingToSettle(): void
    {
        $response = $this->prepare();

        $this->assertTrue($response->isEmpty());
        $this->assertSame([], $response->entries);
        $this->assertSame(0.0, $response->amount);
        $this->assertNull($response->periodFrom);
        $this->assertNull($response->periodTo);
    }

    public function testItIgnoresEntriesOfOtherBeneficiaries(): void
    {
        $this->ledger->add(LedgerEntryMother::forAccount($this->accountId, 48.0));
        $this->ledger->add(LedgerEntryMother::forBioscore(20.0));
        $this->ledger->add(LedgerEntryMother::unassigned(16.0));

        $response = $this->prepare();

        $this->assertCount(1, $response->entries);
        $this->assertSame(48.0, $response->amount);
    }

    public function testItHonoursTheUntilCutoff(): void
    {
        $this->ledger->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) $this->accountId,
            amount: 48.0,
            occurredAt: new \DateTimeImmutable('2026-01-10'),
        ));
        $this->ledger->add(LedgerEntryMother::recorded(
            beneficiary: LedgerBeneficiary::Account,
            beneficiaryId: (string) $this->accountId,
            amount: 16.0,
            occurredAt: new \DateTimeImmutable('2026-03-20'),
        ));

        $response = $this->prepare(new \DateTimeImmutable('2026-02-01'));

        $this->assertCount(1, $response->entries);
        $this->assertSame(48.0, $response->amount);
        $this->assertEquals(new \DateTimeImmutable('2026-01-10'), $response->periodFrom);
        $this->assertEquals(new \DateTimeImmutable('2026-01-10'), $response->periodTo);
    }

    public function testItDeniesNonAdminUsers(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);

        $this->expectException(UnauthorizedActionException::class);

        (new PrepareSettlementService($this->ledger, new SettlementAuthorizer()))(
            new PrepareSettlementRequest(LedgerBeneficiary::Account, (string) $this->accountId),
            $user,
        );
    }

    private function prepare(?\DateTimeImmutable $until = null): PrepareSettlementResponse
    {
        return (new PrepareSettlementService($this->ledger, new SettlementAuthorizer()))(
            new PrepareSettlementRequest(LedgerBeneficiary::Account, (string) $this->accountId, $until),
            UserMother::adminUser(),
        );
    }
}
