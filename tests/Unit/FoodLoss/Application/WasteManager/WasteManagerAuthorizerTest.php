<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\WasteManager;

use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class WasteManagerAuthorizerTest extends TestCase
{
    public function testOwnerCanListWasteManagers(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = WasteManagerMother::fromAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterWasteManagers(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = WasteManagerMother::fromAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditWasteManagerFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = WasteManagerMother::from(name: 'Test', id: (string) WasteManagerId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditWasteManagerFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = WasteManagerMother::from(name: 'Test', id: (string) WasteManagerId::generate(), accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveWasteManagerFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = WasteManagerMother::from(name: 'Test', id: (string) WasteManagerId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::Owner);
        $subject = new WasteManager(WasteManagerId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new WasteManager(WasteManagerId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): WasteManagerAuthorizer
    {
        return new WasteManagerAuthorizer();
    }
}
