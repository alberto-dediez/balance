<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PackagingAuthorizerTest extends TestCase
{
    public function testOwnerCanListPackagings(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = PackagingMother::from(name: 'Test', id: (string) PackagingId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterPackagings(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = PackagingMother::from(name: 'Test', id: (string) PackagingId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditPackagingFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = PackagingMother::from(name: 'Test', id: (string) PackagingId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditPackagingFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = PackagingMother::from(name: 'Test', id: (string) PackagingId::generate(), accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemovePackagingFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', \App\Security\Domain\Model\User\Role::Owner, new AccountId('account-id-test'));
        $subject = PackagingMother::from(name: 'Test', id: (string) PackagingId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('user', \App\Security\Domain\Model\User\Role::Owner);
        $subject = new Packaging(PackagingId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new Packaging(PackagingId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): PackagingAuthorizer
    {
        return new PackagingAuthorizer();
    }
}
