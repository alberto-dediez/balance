<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagException;

use App\FoodLoss\Application\DoggyBagException\DoggyBagExceptionAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DoggyBagExceptionAuthorizerTest extends TestCase
{
    public function testOwnerCanListDoggyBagExceptions(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new DoggyBagException(DoggyBagExceptionId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterDoggyBagExceptions(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new DoggyBagException(DoggyBagExceptionId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditDoggyBagExceptionFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DoggyBagExceptionMother::from(description: 'Test', criticalPointId: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(), accountId: 'account-id-test', id: (string) DoggyBagExceptionId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditDoggyBagExceptionFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DoggyBagExceptionMother::from(description: 'Test', criticalPointId: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(), accountId: 'other-account-id', id: (string) DoggyBagExceptionId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new DoggyBagException(DoggyBagExceptionId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): DoggyBagExceptionAuthorizer
    {
        return new DoggyBagExceptionAuthorizer();
    }
}
