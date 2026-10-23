<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\DataExchange;

use App\Security\Application\User\Export\UsersExportFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\DataExchange\UsersExportationType;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\StubAuthorizationChecker;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class UsersExportationTypeTest extends TestCase
{
    private InMemoryAccountRepository $accountRepository;

    protected function setUp(): void
    {
        $this->accountRepository = new InMemoryAccountRepository();
        $this->accountRepository->add(AccountMother::fromNameAndId('Mi cuenta', 'account-id'));
        $this->accountRepository->add(AccountMother::fromNameAndId('Otra cuenta', 'other-account'));
    }

    public function testAuthorizesTheOwnerToExportTheUsersOfTheCurrentAccount(): void
    {
        self::assertTrue($this->sut()->isAuthorizedForFilter(
            $this->filterForAccount('account-id'),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportTheUsersOfAnotherAccount(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(
            $this->filterForAccount('other-account'),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportTheUsersOfEveryAccount(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(
            new UsersExportFilter(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testAuthorizesTheOwnerToExportTheUsersOfTheImpersonatedAccount(): void
    {
        $this->accountRepository->add(AccountMother::fromNameAndId('Cuenta impersonada', 'impersonated'));
        $sut = $this->sutWithCurrentAccount(new AccountId('impersonated'));
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        self::assertTrue($sut->isAuthorizedForFilter($this->filterForAccount('impersonated'), $owner));
        self::assertFalse($sut->isAuthorizedForFilter($this->filterForAccount('account-id'), $owner));
    }

    public function testAuthorizesAdminsToExportTheUsersOfAnyAccount(): void
    {
        self::assertTrue($this->sut()->isAuthorizedForFilter(new UsersExportFilter(), UserMother::adminUser()));
        self::assertTrue($this->sut()->isAuthorizedForFilter($this->filterForAccount('other-account'), UserMother::adminUser()));
    }

    public function testDoesNotAuthorizeAnotherFilterClass(): void
    {
        self::assertFalse($this->sut()->isAuthorizedForFilter(new \stdClass(), UserMother::adminUser()));
    }

    public function testCreatesAFilterFixedToTheCurrentAccountWhenEveryAccountCannotBeExported(): void
    {
        $filter = $this->sut(granted: false)->createFilter();

        self::assertInstanceOf(UsersExportFilter::class, $filter);
        self::assertSame('account-id', (string) $filter->account?->id());
    }

    public function testCreatesAFilterWithoutAccountWhenEveryAccountCanBeExported(): void
    {
        $filter = $this->sut(granted: true)->createFilter();

        self::assertInstanceOf(UsersExportFilter::class, $filter);
        self::assertNull($filter->account);
    }

    public function testCreatesAFilterWithoutAccountWhenThereIsNoCurrentAccount(): void
    {
        $filter = $this->sut(granted: false, currentAccountId: null)->createFilter();

        self::assertInstanceOf(UsersExportFilter::class, $filter);
        self::assertNull($filter->account);
    }

    public function testCanExportEveryAccountAsTheAuthorizationCheckerSays(): void
    {
        self::assertTrue($this->sut(granted: true)->canExportEveryAccount());
        self::assertFalse($this->sut(granted: false)->canExportEveryAccount());
    }

    private function filterForAccount(string $accountId): UsersExportFilter
    {
        $filter = new UsersExportFilter();
        $filter->account = $this->accountRepository->find(new AccountId($accountId));

        return $filter;
    }

    private function sut(bool $granted = false, ?AccountId $currentAccountId = new AccountId('account-id')): UsersExportationType
    {
        return new UsersExportationType(
            new InMemoryUserRepository(),
            $this->accountRepository,
            UserAuthorizerMother::withCurrentAccount(new AccountId('account-id')),
            new FakeTranslator(),
            new StubAuthorizationChecker($granted),
            new StubCurrentAccountIdProvider($currentAccountId),
        );
    }

    private function sutWithCurrentAccount(AccountId $currentAccountId): UsersExportationType
    {
        return new UsersExportationType(
            new InMemoryUserRepository(),
            $this->accountRepository,
            UserAuthorizerMother::withCurrentAccount($currentAccountId),
            new FakeTranslator(),
            new StubAuthorizationChecker(false),
            new StubCurrentAccountIdProvider($currentAccountId),
        );
    }
}
