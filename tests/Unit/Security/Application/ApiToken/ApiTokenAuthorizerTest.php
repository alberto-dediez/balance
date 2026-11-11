<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\ApiToken;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ApiTokenAuthorizerTest extends TestCase
{
    private AccountId $accountId;

    private User $tokenOwner;

    private ApiToken $apiToken;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->tokenOwner = UserMother::fromUsernameAndRoleAndAccount('owner-of-token@example.com', Role::User, $this->accountId);
        $this->apiToken = ApiTokenMother::active($this->tokenOwner->id(), $this->accountId);
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->userRepository->add($this->tokenOwner);
    }

    /**
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function matrix(): array
    {
        return [
            'token owner' => ['tokenOwner', true, true, true],
            'admin' => ['admin', false, true, true],
            'super admin' => ['superAdmin', false, true, true],
            'owner of the same account' => ['sameAccountOwner', false, true, true],
            'owner of another account' => ['otherAccountOwner', false, false, false],
            'another user of the same account' => ['sameAccountUser', false, false, false],
        ];
    }

    /**
     * @dataProvider matrix
     */
    public function testMatrix(string $actor, bool $canRegister, bool $canList, bool $canRemove): void
    {
        $user = $this->actor($actor);
        $sut = new ApiTokenAuthorizer($this->userRepository);

        $this->assertSame($canRegister, $sut->canDoThat($this->apiToken, Action::Register, $user));
        $this->assertSame($canList, $sut->canDoThat($this->apiToken, Action::List, $user));
        $this->assertSame($canList, $sut->canDoThat($this->apiToken, Action::View, $user));
        $this->assertSame($canRemove, $sut->canDoThat($this->apiToken, Action::Remove, $user));
        $this->assertFalse($sut->canDoThat($this->apiToken, Action::Edit, $user));
    }

    public function testAdminCannotListNorRemoveTokensOfASuperAdmin(): void
    {
        $superAdmin = UserMother::superAdminUser();
        $this->userRepository->add($superAdmin);
        $apiToken = ApiTokenMother::active($superAdmin->id());
        $sut = new ApiTokenAuthorizer($this->userRepository);
        $admin = UserMother::adminUser();

        $this->assertFalse($sut->canDoThat($apiToken, Action::List, $admin));
        $this->assertFalse($sut->canDoThat($apiToken, Action::View, $admin));
        $this->assertFalse($sut->canDoThat($apiToken, Action::Remove, $admin));
    }

    public function testSuperAdminCanListAndRemoveTokensOfAnotherSuperAdmin(): void
    {
        $superAdmin = UserMother::superAdminUser();
        $this->userRepository->add($superAdmin);
        $apiToken = ApiTokenMother::active($superAdmin->id());
        $sut = new ApiTokenAuthorizer($this->userRepository);
        $anotherSuperAdmin = UserMother::fromUsernameAndRole('another-super-admin@example.com', Role::SuperAdmin);

        $this->assertTrue($sut->canDoThat($apiToken, Action::List, $anotherSuperAdmin));
        $this->assertTrue($sut->canDoThat($apiToken, Action::Remove, $anotherSuperAdmin));
    }

    public function testAdminCannotManageTokensOfAnUnknownUser(): void
    {
        $apiToken = ApiTokenMother::active(UserMother::fromUsername('unknown@example.com')->id());
        $sut = new ApiTokenAuthorizer($this->userRepository);

        $this->assertFalse($sut->canDoThat($apiToken, Action::Remove, UserMother::adminUser()));
    }

    private function actor(string $actor): User
    {
        return match ($actor) {
            'tokenOwner' => $this->tokenOwner,
            'admin' => UserMother::adminUser(),
            'superAdmin' => UserMother::superAdminUser(),
            'sameAccountOwner' => UserMother::fromUsernameAndRoleAndAccount('owner@example.com', Role::Owner, $this->accountId),
            'otherAccountOwner' => UserMother::fromUsernameAndRoleAndAccount('other-owner@example.com', Role::Owner, AccountId::generate()),
            default => UserMother::fromUsernameAndRoleAndAccount('user@example.com', Role::User, $this->accountId),
        };
    }
}
