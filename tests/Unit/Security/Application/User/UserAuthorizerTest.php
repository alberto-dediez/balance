<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User;

use App\Security\Application\Action;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;

class UserAuthorizerTest extends TestCase
{
    public function testOwnerCanGrantRoleToNotAdminRoles(): void
    {
        $account = AccountMother::random();

        $user = UserMother::fromUsernameAndAccount('username', $account);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $sut = $this->createSUT();

        $result = $sut->canChangeRole($user, Role::Owner, $performer);

        $this->assertTrue($result);
    }

    public function testOwnerCannotGrantRoleToAdminRoles(): void
    {
        $account = AccountMother::random();

        $user = UserMother::fromUsernameAndAccount('username', $account);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $sut = $this->createSUT();

        $result = $sut->canChangeRole($user, Role::Admin, $performer);

        $this->assertFalse($result);
    }

    public function testOwnerCannotGrantRoleToUserFromOtherAccounts(): void
    {
        $account = AccountMother::random();

        $user = UserMother::fromUsernameAndAccount('username', $account);
        $performer = UserMother::ownerUser();

        $sut = $this->createSUT();

        $result = $sut->canChangeRole($user, Role::Owner, $performer);

        $this->assertFalse($result);
    }

    public function testSuperAdminCanImpersonateAnotherUser(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(UserMother::random(), Action::Impersonate, UserMother::superAdminUser());

        $this->assertTrue($result);
    }

    public function testSuperAdminCannotImpersonateItself(): void
    {
        $sut = $this->createSUT();

        $superAdmin = UserMother::superAdminUser();

        $result = $sut->canDoThat($superAdmin, Action::Impersonate, $superAdmin);

        $this->assertFalse($result);
    }

    public function testAdminCannotImpersonate(): void
    {
        $sut = $this->createSUT();

        $result = $sut->canDoThat(UserMother::random(), Action::Impersonate, UserMother::adminUser());

        $this->assertFalse($result);
    }

    public function testOwnerEditsTheUsersOfTheCurrentAccountWhenItIsSwitched(): void
    {
        $switchedAccount = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $switchedAccount);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $sut = $this->createSUTWithCurrentAccount($switchedAccount->id());

        $this->assertTrue($sut->canDoThat($subject, Action::List, $performer));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $performer));
    }

    public function testOwnerDoesNotEditTheUsersOfItsOwnAccountWhileAnotherOneIsCurrent(): void
    {
        $ownAccount = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $ownAccount);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $ownAccount->id());

        $sut = $this->createSUTWithCurrentAccount(AccountId::generate());

        $this->assertFalse($sut->canDoThat($subject, Action::List, $performer));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $performer));
    }

    public function testOwnerGrantsRolesWithinTheCurrentAccountWhenItIsSwitched(): void
    {
        $switchedAccount = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $switchedAccount);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $sut = $this->createSUTWithCurrentAccount($switchedAccount->id());

        $this->assertTrue($sut->canChangeRole($subject, Role::Owner, $performer));
    }

    public function testTheAccountOfTheUserIsUsedWhenThereIsNoCurrentAccount(): void
    {
        $account = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $account);
        $performer = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $sut = $this->createSUTWithCurrentAccount(null);

        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $performer));
    }

    public function testImpersonatingAdminActsAsOwnerOfTheImpersonatedAccount(): void
    {
        $impersonated = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $impersonated);
        $admin = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $sut = $this->createSUTWithImpersonation($impersonated->id());

        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $admin));
    }

    public function testImpersonatingAdminCannotEditUsersOfAnotherAccount(): void
    {
        $impersonated = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', AccountMother::random());
        $admin = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $sut = $this->createSUTWithImpersonation($impersonated->id());

        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $admin));
    }

    public function testImpersonatingAdminCannotGrantAdminRoles(): void
    {
        $impersonated = AccountMother::random();

        $subject = UserMother::fromUsernameAndAccount('username', $impersonated);
        $admin = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $sut = $this->createSUTWithImpersonation($impersonated->id());

        $this->assertFalse($sut->canChangeRole($subject, Role::Admin, $admin));
    }

    private function createSUT(): UserAuthorizer
    {
        return new UserAuthorizer();
    }

    private function createSUTWithCurrentAccount(?AccountId $currentAccountId): UserAuthorizer
    {
        return new UserAuthorizer(new StubCurrentAccountIdProvider($currentAccountId));
    }

    private function createSUTWithImpersonation(AccountId $impersonatedAccountId): UserAuthorizer
    {
        return new UserAuthorizer(
            new StubCurrentAccountIdProvider($impersonatedAccountId),
            new StubCurrentRolesProvider([Role::Owner, Role::User]),
        );
    }
}
