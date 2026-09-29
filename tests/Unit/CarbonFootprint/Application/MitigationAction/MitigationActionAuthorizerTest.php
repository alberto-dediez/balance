<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MitigationActionAuthorizerTest extends TestCase
{
    public function testOwnerOfTheAccountCanManage(): void
    {
        $action = MitigationActionMother::manual(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $securityAction) {
            $this->assertTrue((new MitigationActionAuthorizer())->canDoThat($action, $securityAction, $owner), $securityAction->value);
        }
    }

    public function testOwnerOfAnotherAccountCannotManage(): void
    {
        $action = MitigationActionMother::manual(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $securityAction) {
            $this->assertFalse((new MitigationActionAuthorizer())->canDoThat($action, $securityAction, $owner), $securityAction->value);
        }
    }

    public function testUserRoleCannotManage(): void
    {
        $action = MitigationActionMother::manual(accountId: 'account-id');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $securityAction) {
            $this->assertFalse((new MitigationActionAuthorizer())->canDoThat($action, $securityAction, $user), $securityAction->value);
        }
    }

    public function testAdminCanOnlyRemove(): void
    {
        $action = MitigationActionMother::manual(accountId: 'account-id');

        $this->assertTrue((new MitigationActionAuthorizer())->canDoThat($action, Action::Remove, UserMother::adminUser()));
        $this->assertFalse((new MitigationActionAuthorizer())->canDoThat($action, Action::Edit, UserMother::adminUser()));
    }
}
