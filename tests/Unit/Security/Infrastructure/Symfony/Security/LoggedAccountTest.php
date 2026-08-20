<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class LoggedAccountTest extends TestCase
{
    public function testValidImpersonationReturnsTheImpersonatedAccount(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);

        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $account = $sut();

        $this->assertSame((string) $acmeAccount->id(), $this->idOf($account));
        $this->assertSame((string) $acmeAccount->id(), (string) $sut->currentAccountId());
    }

    public function testImpersonatorWhoIsNoLongerAdminFallsBackToOwnAccount(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);

        $impersonatorId = UserId::generate();
        $downgraded = $this->userOfAccountWithId($impersonatorId, Role::Owner, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $impersonatorId);

        $sut = $this->buildSut($repository, $impersonated, $downgraded);

        $account = $sut();

        $this->assertSame((string) $adminAccount->id(), $this->idOf($account));
        $this->assertFalse($impersonated->isImpersonating());
    }

    public function testInactiveImpersonatedAccountFallsBackToOwnAccount(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');
        $acmeAccount->changeStatus(false);

        $repository = $this->buildRepository($adminAccount, $acmeAccount);

        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $account = $sut();

        $this->assertSame((string) $adminAccount->id(), $this->idOf($account));
        $this->assertFalse($impersonated->isImpersonating());
    }

    public function testImpersonatorIdMismatchFallsBackToOwnAccount(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);

        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), UserId::generate());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $account = $sut();

        $this->assertSame((string) $adminAccount->id(), $this->idOf($account));
        $this->assertFalse($impersonated->isImpersonating());
    }

    public function testWithoutImpersonationReturnsTheOwnAccount(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');

        $repository = $this->buildRepository($adminAccount);

        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $account = $sut();

        $this->assertSame((string) $adminAccount->id(), $this->idOf($account));
    }

    public function testWithoutImpersonationAndWithoutOwnAccountReturnsNull(): void
    {
        $admin = UserMother::adminUser();

        $repository = $this->buildRepository();
        $impersonated = $this->buildImpersonated($repository);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new SecurityUser($admin));

        $loggedUser = new LoggedUser($security, new InMemoryUserRepository());
        $loggedUser->forceUser($admin);

        $sut = new LoggedAccount($security, $repository, $impersonated, $loggedUser, new ImpersonationPolicy());

        $this->assertNull($sut());
        $this->assertNull($sut->currentAccountId());
    }

    public function testResetForcesRevalidationOnNextCall(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);

        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $this->assertSame((string) $acmeAccount->id(), $this->idOf($sut()));

        $acmeAccount->changeStatus(false);
        $sut->reset();

        $account = $sut();

        $this->assertSame((string) $adminAccount->id(), $this->idOf($account));
        $this->assertFalse($impersonated->isImpersonating());
    }

    private function buildRepository(Account ...$accounts): InMemoryAccountRepository
    {
        $repository = new InMemoryAccountRepository();

        foreach ($accounts as $account) {
            $repository->add($account);
        }

        return $repository;
    }

    private function buildImpersonated(InMemoryAccountRepository $repository): ImpersonatedAccount
    {
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new ImpersonatedAccount($requestStack, $repository, true);
    }

    private function buildSut(InMemoryAccountRepository $repository, ImpersonatedAccount $impersonated, User $loggedUser): LoggedAccount
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new SecurityUser($loggedUser));

        $loggedUserService = new LoggedUser($security, $this->buildUserRepository());
        $loggedUserService->forceUser($loggedUser);

        return new LoggedAccount($security, $repository, $impersonated, $loggedUserService, new ImpersonationPolicy());
    }

    private function buildUserRepository(): UserRepository
    {
        return new InMemoryUserRepository();
    }

    private function userOfAccount(Role $role, AccountId $accountId): User
    {
        return $this->userOfAccountWithId(UserId::generate(), $role, $accountId);
    }

    private function userOfAccountWithId(UserId $userId, Role $role, AccountId $accountId): User
    {
        return UserMother::from(
            userId: $userId,
            username: 'user-' . $userId->id(),
            role: $role,
            accountId: $accountId,
        );
    }

    private function idOf(?Account $account): ?string
    {
        return $account instanceof Account ? (string) $account->id() : null;
    }
}
