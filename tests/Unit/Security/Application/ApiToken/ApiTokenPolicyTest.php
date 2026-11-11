<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ApiTokenPolicyTest extends TestCase
{
    public function testUserWithoutAccountCanHaveTokens(): void
    {
        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());

        $this->assertTrue($sut->userCanHaveTokens(UserMother::adminUser()));
    }

    public function testUserOfAccountWithPermissionCanHaveTokens(): void
    {
        $account = AccountMother::withApiTokensEnabled('Allowed', 'allowed-account');
        $user = UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $account->id(), $account);

        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());

        $this->assertTrue($sut->userCanHaveTokens($user));
    }

    public function testUserOfAccountWithoutPermissionCannotHaveTokens(): void
    {
        $account = AccountMother::fromNameAndId('Denied', 'denied-account');
        $user = UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $account->id(), $account);

        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());

        $this->assertFalse($sut->userCanHaveTokens($user));
    }

    public function testTokenWithoutAccountIsUsable(): void
    {
        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());

        $this->assertTrue($sut->isUsable(ApiTokenMother::active(UserId::generate()), new \DateTimeImmutable('NOW')));
    }

    public function testTokenOfAccountWithPermissionIsUsable(): void
    {
        $repository = AccountRepositoryMother::inMemory();
        $account = AccountMother::withApiTokensEnabled('Allowed', 'allowed-account');
        $repository->add($account);

        $sut = new ApiTokenPolicy($repository);

        $this->assertTrue($sut->isUsable(ApiTokenMother::active(UserId::generate(), $account->id()), new \DateTimeImmutable('NOW')));
    }

    public function testTokenOfAccountWithoutPermissionIsNotUsable(): void
    {
        $repository = AccountRepositoryMother::inMemory();
        $account = AccountMother::fromNameAndId('Denied', 'denied-account');
        $repository->add($account);

        $sut = new ApiTokenPolicy($repository);

        $this->assertFalse($sut->isUsable(ApiTokenMother::active(UserId::generate(), $account->id()), new \DateTimeImmutable('NOW')));
    }

    public function testTokenOfUnknownAccountIsNotUsable(): void
    {
        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());

        $this->assertFalse($sut->isUsable(ApiTokenMother::active(UserId::generate(), AccountId::generate()), new \DateTimeImmutable('NOW')));
    }

    public function testInactiveTokenIsNotUsable(): void
    {
        $sut = new ApiTokenPolicy(AccountRepositoryMother::inMemory());
        $userId = UserId::generate();

        $this->assertFalse($sut->isUsable(ApiTokenMother::revoked($userId), new \DateTimeImmutable('NOW')));
        $this->assertFalse($sut->isUsable(ApiTokenMother::expired($userId), new \DateTimeImmutable('NOW')));
    }
}
