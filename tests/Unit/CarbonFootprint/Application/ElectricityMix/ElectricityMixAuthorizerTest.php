<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ElectricityMixAuthorizerTest extends TestCase
{
    public function testAdminCanListElectricityMixes(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterElectricityMixes(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditElectricityMix(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveElectricityMix(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListElectricityMixes(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListElectricityMixes(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new ElectricityMix(ElectricityMixId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): ElectricityMixAuthorizer
    {
        return new ElectricityMixAuthorizer();
    }
}
