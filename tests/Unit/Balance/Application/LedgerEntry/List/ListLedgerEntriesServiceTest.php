<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\List;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesRequest;
use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesService;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryLedgerEntryRepository;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryMother;
use App\Tests\Unit\Balance\Domain\Model\LedgerEntry\LedgerEntryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListLedgerEntriesServiceTest extends TestCase
{
    private InMemoryLedgerEntryRepository $repository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = LedgerEntryRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAnAdminListsAllEntries(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount());
        $this->repository->add(LedgerEntryMother::forBioscore());
        $this->repository->add(LedgerEntryMother::forProject());

        $response = $this->service()(
            new ListLedgerEntriesRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertCount(3, $response->ledgerEntries);
        $this->assertSame(3, $response->totalResults);
    }

    public function testAnAdminFiltersByBeneficiary(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount());
        $this->repository->add(LedgerEntryMother::forAccount());
        $this->repository->add(LedgerEntryMother::forProject());

        $response = $this->service()(
            new ListLedgerEntriesRequest(
                beneficiary: LedgerBeneficiary::Account,
                limit: 0,
            ),
            UserMother::adminUser(),
        );

        $this->assertCount(2, $response->ledgerEntries);
        $this->assertSame(2, $response->totalResults);
    }

    public function testAnAdminFiltersByType(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $this->repository->add(LedgerEntryMother::forBioscore(20.0, LedgerEntryType::ManagementFee));

        $response = $this->service()(
            new ListLedgerEntriesRequest(
                type: LedgerEntryType::ManagementFee,
                limit: 0,
            ),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $response->ledgerEntries);
    }

    public function testAnAdminFiltersBySettledStatus(): void
    {
        $settled = LedgerEntryMother::forAccount();
        $settled->settle(PayoutId::generate());

        $this->repository->add($settled);
        $this->repository->add(LedgerEntryMother::forAccount());

        $response = $this->service()(
            new ListLedgerEntriesRequest(
                beneficiary: LedgerBeneficiary::Account,
                settled: true,
                limit: 0,
            ),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $response->ledgerEntries);
        $this->assertTrue($response->ledgerEntries[0]->id()->equals($settled->id()));
    }

    public function testAnOwnerOnlySeesTheirOwnAccountEntries(): void
    {
        $accountId = AccountId::generate();

        $this->repository->add(LedgerEntryMother::forAccount($accountId, 48.0));
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));
        $this->repository->add(LedgerEntryMother::forBioscore());
        $this->repository->add(LedgerEntryMother::forProject());

        $response = $this->service()(
            new ListLedgerEntriesRequest(limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );

        $this->assertCount(1, $response->ledgerEntries);
        $this->assertSame(1, $response->totalResults);
        $this->assertSame((string) $accountId, $response->ledgerEntries[0]->beneficiaryId());
    }

    public function testAPlainUserIsNotAuthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ListLedgerEntriesRequest(limit: 0),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): ListLedgerEntriesService
    {
        return new ListLedgerEntriesService($this->repository, new LedgerEntryAuthorizer());
    }
}
