<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment;

use App\Payments\Application\Payment\PaymentAuthorizer;
use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PaymentAuthorizerTest extends TestCase
{
    public function testAdminCanDoEverything(): void
    {
        $user = UserMother::adminUser();

        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Remove, $user));
    }

    public function testOwnerCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
    }

    public function testUserCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Remove, $user));
    }

    public function testSupportsOnlyPayments(): void
    {
        $this->assertTrue($this->createSUT()->supports($this->subject()));
        $this->assertFalse($this->createSUT()->supports(PaymentId::generate()));
    }

    private function subject(): Payment
    {
        return new Payment(id: PaymentId::generate());
    }

    private function createSUT(): PaymentAuthorizer
    {
        return new PaymentAuthorizer();
    }
}
