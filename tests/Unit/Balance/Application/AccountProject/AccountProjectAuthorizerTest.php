<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\AccountProject;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
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

    public function testOwnerCannotDoAnythingWithoutCompensationFeature(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $user));
        }
    }

    public function testAdminCanListWithCompensationFeatureDisabled(): void
    {
        $accountId = AccountId::generate();
        $subject = new AccountProject(id: AccountProjectId::generate(), accountId: $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($subject, Action::Register, UserMother::adminUser()));
    }

    private function createSUT(): AccountProjectAuthorizer
    {
        return new AccountProjectAuthorizer();
    }

    private function createSUTWithFeatures(AccountId $accountId, AccountFeatures $features): AccountProjectAuthorizer
    {
        return new AccountProjectAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($accountId),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($accountId, $features),
        );
    }
}
