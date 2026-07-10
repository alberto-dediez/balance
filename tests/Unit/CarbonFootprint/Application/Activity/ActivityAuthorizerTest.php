<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ActivityAuthorizerTest extends TestCase
{
    public function testOwnerCanEditActivityFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditActivityFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerWithoutAccountCannotEditActivity(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testProviderCanEditActivityFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('provider', Role::Provider, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testProviderCannotEditActivityFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('provider', Role::Provider, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testProviderWithoutAccountCannotEditActivity(): void
    {
        $user = UserMother::fromUsernameAndRole('provider', Role::Provider);
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCannotEditActivity(): void
    {
        $user = UserMother::adminUser();
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testSuperAdminCannotEditActivity(): void
    {
        $user = UserMother::fromUsernameAndRole('super-admin', Role::SuperAdmin);
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveActivityFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotRemoveActivityFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCanViewActivityFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = MaterialActivityMother::registered(new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testRegisterAndListWithDummySubjectFollowRoleBasedRules(): void
    {
        $owner = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new MaterialActivity(id: ActivityId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $owner));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $owner));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): ActivityAuthorizer
    {
        return new ActivityAuthorizer();
    }
}
