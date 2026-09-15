<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RefrigerantGasAuthorizerTest extends TestCase
{
    public function testAdminCanListRefrigerantGases(): void
    {
        $user = UserMother::adminUser();
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterRefrigerantGases(): void
    {
        $user = UserMother::adminUser();
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditRefrigerantGas(): void
    {
        $user = UserMother::adminUser();
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveRefrigerantGas(): void
    {
        $user = UserMother::adminUser();
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListRefrigerantGases(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListRefrigerantGases(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new RefrigerantGas(RefrigerantGasId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): RefrigerantGasAuthorizer
    {
        return new RefrigerantGasAuthorizer();
    }
}
