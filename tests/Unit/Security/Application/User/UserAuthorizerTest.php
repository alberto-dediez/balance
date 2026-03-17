<?php

namespace App\Tests\Unit\Security\Application\User;

use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
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

    private function createSUT(): UserAuthorizer
    {
        return new UserAuthorizer();
    }
}
