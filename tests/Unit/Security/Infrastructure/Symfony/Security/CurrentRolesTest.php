<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Application\Account\ImpersonationPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\CurrentRoles;
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

class CurrentRolesTest extends TestCase
{
    public function testWithoutImpersonationThereIsNoRoleOverride(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');

        $repository = $this->buildRepository($adminAccount);
        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $sut = $this->buildSut($repository, $this->buildImpersonated($repository), $admin);

        $this->assertNull($sut->currentRoles($admin));
        $this->assertNull($sut->currentLoggedUserRoles());
    }

    public function testImpersonatingAdminActsAsOwner(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);
        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $this->assertSame([Role::Owner, Role::User], $sut->currentRoles($admin));
        $this->assertSame([Role::Owner, Role::User], $sut->currentLoggedUserRoles());
    }

    public function testImpersonatingAdminNoLongerActsAsAdmin(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);
        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $currentRoles = $sut->currentRoles($admin);

        $this->assertNotNull($currentRoles);
        $this->assertNotContains(Role::Admin, $currentRoles);
    }

    public function testAnotherUserThanTheImpersonatorKeepsItsOwnRoles(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');

        $repository = $this->buildRepository($adminAccount, $acmeAccount);
        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());
        $another = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $this->assertNull($sut->currentRoles($another));
    }

    public function testInvalidImpersonationDoesNotOverrideRoles(): void
    {
        $adminAccount = AccountMother::fromNameAndId('AdminAccount', 'admin-account-id');
        $acmeAccount = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');
        $acmeAccount->changeStatus(false);

        $repository = $this->buildRepository($adminAccount, $acmeAccount);
        $admin = $this->userOfAccount(Role::Admin, $adminAccount->id());

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($acmeAccount->id(), $admin->id());

        $sut = $this->buildSut($repository, $impersonated, $admin);

        $this->assertNull($sut->currentRoles($admin));
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

    private function buildSut(InMemoryAccountRepository $repository, ImpersonatedAccount $impersonated, User $loggedUser): CurrentRoles
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new SecurityUser($loggedUser));

        $loggedUserService = new LoggedUser($security, new InMemoryUserRepository());
        $loggedUserService->forceUser($loggedUser);

        $loggedAccount = new LoggedAccount($security, $repository, $impersonated, $loggedUserService, new ImpersonationPolicy());

        return new CurrentRoles($impersonated, $loggedAccount, $loggedUserService);
    }

    private function userOfAccount(Role $role, AccountId $accountId): User
    {
        $userId = UserId::generate();

        return UserMother::from(
            userId: $userId,
            username: 'user-' . $userId->id(),
            role: $role,
            accountId: $accountId,
        );
    }
}
