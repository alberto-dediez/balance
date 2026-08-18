<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\LedgerEntry\Balance;

use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesRequest;
use App\Balance\Application\LedgerEntry\Balance\ListLedgerBalancesService;
use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
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

class ListLedgerBalancesServiceTest extends TestCase
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

    public function testAnAdminListsAllBalancesForABeneficiary(): void
    {
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 48.0));
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));
        $this->repository->add(LedgerEntryMother::forBioscore());

        $response = $this->service()(
            new ListLedgerBalancesRequest(beneficiary: LedgerBeneficiary::Account),
            UserMother::adminUser(),
        );

        $this->assertCount(2, $response->balances);
    }

    public function testAnAdminGetsAnEmptyBalanceListWhenNoEntriesExist(): void
    {
        $response = $this->service()(
            new ListLedgerBalancesRequest(beneficiary: LedgerBeneficiary::Project),
            UserMother::adminUser(),
        );

        $this->assertSame([], $response->balances);
    }

    public function testAnOwnerOnlyReceivesTheirOwnAccountBalance(): void
    {
        $accountId = AccountId::generate();

        $this->repository->add(LedgerEntryMother::forAccount($accountId, 48.0));
        $this->repository->add(LedgerEntryMother::forAccount(AccountId::generate(), 32.0));

        $response = $this->service()(
            new ListLedgerBalancesRequest(beneficiary: LedgerBeneficiary::Account),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );

        $this->assertArrayHasKey((string) $accountId, $response->balances);
        $this->assertCount(1, $response->balances);
        $this->assertSame(48.0, $response->balances[(string) $accountId]->accruedAmount());
    }

    public function testAPlainUserIsNotAuthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ListLedgerBalancesRequest(beneficiary: LedgerBeneficiary::Account),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): ListLedgerBalancesService
    {
        return new ListLedgerBalancesService($this->repository, new LedgerEntryAuthorizer());
    }
}
