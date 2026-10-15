<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ExchangeRate;

use App\CarbonFootprint\Application\ExchangeRate\ExchangeRateAuthorizer;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExchangeRateAuthorizerTest extends TestCase
{
    public function testAdminCanListViewRegisterAndAdmin(): void
    {
        $user = UserMother::adminUser();

        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
    }

    public function testSuperAdminCanListViewRegisterAndAdmin(): void
    {
        $user = UserMother::superAdminUser();

        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
    }

    public function testOwnerAndUserCannot(): void
    {
        foreach ([Role::Owner, Role::User] as $role) {
            $user = UserMother::fromUsernameAndRole('someone', $role);

            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
        }
    }

    public function testNobodyCanEditOrRemove(): void
    {
        foreach ([UserMother::superAdminUser(), UserMother::adminUser()] as $user) {
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Remove, $user));
        }
    }

    public function testDoesNotSupportOtherSubjects(): void
    {
        $this->assertFalse($this->createSUT()->canDoThat(new \stdClass(), Action::List, UserMother::adminUser()));
    }

    private function subject(): ExchangeRate
    {
        return new ExchangeRate(id: ExchangeRateId::generate());
    }

    private function createSUT(): ExchangeRateAuthorizer
    {
        return new ExchangeRateAuthorizer();
    }
}
