<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\AccountProject;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AccountProjectAuthorizerTest extends TestCase
{
    public function testOwnerCanRegisterForOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCanEditOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanListAndRegisterButNotEditOrRemove(): void
    {
        $user = UserMother::adminUser();
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: AccountId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    private function createSUT(): AccountProjectAuthorizer
    {
        return new AccountProjectAuthorizer();
    }
}
