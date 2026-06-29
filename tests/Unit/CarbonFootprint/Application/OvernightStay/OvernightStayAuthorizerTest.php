<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class OvernightStayAuthorizerTest extends TestCase
{
    public function testAdminCanListOvernightStays(): void
    {
        $user = UserMother::adminUser();
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterOvernightStays(): void
    {
        $user = UserMother::adminUser();
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditOvernightStay(): void
    {
        $user = UserMother::adminUser();
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveOvernightStay(): void
    {
        $user = UserMother::adminUser();
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListOvernightStays(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListOvernightStays(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new OvernightStay(OvernightStayId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): OvernightStayAuthorizer
    {
        return new OvernightStayAuthorizer();
    }
}
