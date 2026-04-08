<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagSign;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DoggyBagSignAuthorizerTest extends TestCase
{
    public function testOwnerCanListDoggyBagSigns(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterDoggyBagSign(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditDoggyBagSignFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditDoggyBagSignFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveDoggyBagSignFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListWithoutAccount(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCanListDoggyBagSigns(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCanViewDoggyBagSign(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testUserCannotEditDoggyBagSign(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserCannotEditDoggyBagSignFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserCannotRemoveDoggyBagSign(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserCannotRemoveDoggyBagSignFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserCannotRegisterDoggyBagSign(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testUserCannotListSignsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: new AccountId('other-account-id'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotViewDoggyBagSignFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = DoggyBagSignMother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testUserWithoutAccountCannotListOrView(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = DoggyBagSignMother::forAccount('account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testAdminCannotDoAnything(): void
    {
        $user = UserMother::adminUser();
        $subject = DoggyBagSignMother::random();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): DoggyBagSignAuthorizer
    {
        return new DoggyBagSignAuthorizer();
    }
}
