<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MachineryFuelAuthorizerTest extends TestCase
{
    public function testAdminCanListMachineryFuels(): void
    {
        $user = UserMother::adminUser();
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterMachineryFuels(): void
    {
        $user = UserMother::adminUser();
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditMachineryFuel(): void
    {
        $user = UserMother::adminUser();
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveMachineryFuel(): void
    {
        $user = UserMother::adminUser();
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListMachineryFuels(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListMachineryFuels(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new MachineryFuel(MachineryFuelId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): MachineryFuelAuthorizer
    {
        return new MachineryFuelAuthorizer();
    }
}
