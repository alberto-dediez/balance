<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreditPurchaseAuthorizerTest extends TestCase
{
    public function testOwnerCanRegisterAndRemoveForOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $subject = new CreditPurchase(id: CreditPurchaseId::generate(), accountId: $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());
        $subject = new CreditPurchase(id: CreditPurchaseId::generate(), accountId: AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testAdminCanListAndEditButNotRegister(): void
    {
        $user = UserMother::adminUser();
        $subject = new CreditPurchase(id: CreditPurchaseId::generate(), accountId: AccountId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testUserCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new CreditPurchase(id: CreditPurchaseId::generate(), accountId: AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    private function createSUT(): CreditPurchaseAuthorizer
    {
        return new CreditPurchaseAuthorizer();
    }
}
