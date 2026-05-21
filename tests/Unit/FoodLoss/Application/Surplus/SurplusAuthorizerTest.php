<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SurplusAuthorizerTest extends TestCase
{
    public function testOwnerCanListSurpluses(): void
    {
        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);
        $subject = new Surplus(SurplusId::generate(), accountId: $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterSurpluses(): void
    {
        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);
        $subject = new Surplus(SurplusId::generate(), accountId: $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditSurplusFromSameAccount(): void
    {
        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);
        $subject = SurplusMother::withAccountId($accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditSurplusFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = SurplusMother::withAccountId(new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveSurplusFromSameAccount(): void
    {
        $accountId = new AccountId('account-id-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, $accountId);
        $subject = SurplusMother::withAccountId($accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testAdminCannotListSurpluses(): void
    {
        $user = UserMother::adminUser();
        $subject = new Surplus(SurplusId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): SurplusAuthorizer
    {
        return new SurplusAuthorizer();
    }
}
