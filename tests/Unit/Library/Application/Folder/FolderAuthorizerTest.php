<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;

class FolderAuthorizerTest extends TestCase
{
    private const array MANAGE = [Action::Register, Action::Edit, Action::Remove];

    private const array READ = [Action::View, Action::List];

    public function testOwnersAdminsAndSuperAdminsOfTheAccountManageTheFolders(): void
    {
        $sut = FolderAuthorizerMother::create();

        $accountId = AccountId::generate();
        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);

        foreach ([Role::Owner, Role::Admin, Role::SuperAdmin] as $role) {
            $user = UserMother::fromUsernameAndRoleAndAccount('someone@dediez.software', $role, $accountId);

            foreach ([...self::MANAGE, ...self::READ] as $action) {
                $this->assertTrue($sut->canDoThat($folder, $action, $user), $role->value . ' ' . $action->value);
            }
        }
    }

    public function testUsersOfTheAccountOnlyViewAndList(): void
    {
        $sut = FolderAuthorizerMother::create();

        $accountId = AccountId::generate();
        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId);

        foreach (self::READ as $action) {
            $this->assertTrue($sut->canDoThat($folder, $action, $user), $action->value);
        }

        foreach (self::MANAGE as $action) {
            $this->assertFalse($sut->canDoThat($folder, $action, $user), $action->value);
        }
    }

    public function testNobodyReachesTheFoldersOfAnotherAccount(): void
    {
        $sut = FolderAuthorizerMother::create();

        $folder = FolderMother::fromNameAndAccount('Contratos', AccountId::generate());

        foreach ([Role::User, Role::Owner, Role::Admin, Role::SuperAdmin] as $role) {
            $user = UserMother::fromUsernameAndRoleAndAccount('someone@dediez.software', $role, AccountId::generate());

            foreach ([...self::MANAGE, ...self::READ] as $action) {
                $this->assertFalse($sut->canDoThat($folder, $action, $user), $role->value . ' ' . $action->value);
            }
        }
    }

    public function testUsersWithoutAccountReachTheFoldersWithoutAccount(): void
    {
        $sut = FolderAuthorizerMother::create();

        $folder = FolderMother::fromName('Contratos');

        $this->assertTrue($sut->canDoThat($folder, Action::Register, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($folder, Action::View, UserMother::fromUsernameAndRole('user@dediez.software', Role::User)));
        $this->assertFalse($sut->canDoThat($folder, Action::Register, UserMother::fromUsernameAndRole('user@dediez.software', Role::User)));

        $withAccount = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, AccountId::generate());

        $this->assertFalse($sut->canDoThat($folder, Action::View, $withAccount));
        $this->assertFalse($sut->canDoThat(FolderMother::fromNameAndAccount('Contratos', AccountId::generate()), Action::View, UserMother::adminUser()));
    }

    public function testTheImpersonatedAccountIsTheOneThatCounts(): void
    {
        $impersonated = AccountId::generate();

        $sut = new FolderAuthorizer(
            new StubCurrentAccountIdProvider($impersonated),
            new StubCurrentRolesProvider([Role::Owner]),
        );

        $admin = UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, AccountId::generate());

        $this->assertTrue($sut->canDoThat(FolderMother::fromNameAndAccount('Contratos', $impersonated), Action::Register, $admin));
        $this->assertFalse($sut->canDoThat(FolderMother::fromNameAndAccount('Propia', $admin->accountId() ?? AccountId::generate()), Action::View, $admin));
    }

    public function testItOnlySupportsFolders(): void
    {
        $sut = FolderAuthorizerMother::create();

        $this->assertFalse($sut->supports(new \stdClass()));
        $this->assertFalse($sut->canDoThat(new \stdClass(), Action::List, UserMother::adminUser()));
    }
}
