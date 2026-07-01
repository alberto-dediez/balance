<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EmissionFactorGroupAuthorizerTest extends TestCase
{
    public function testAdminCanListEmissionFactorGroups(): void
    {
        $user = UserMother::adminUser();
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterEmissionFactorGroups(): void
    {
        $user = UserMother::adminUser();
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditEmissionFactorGroup(): void
    {
        $user = UserMother::adminUser();
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveEmissionFactorGroup(): void
    {
        $user = UserMother::adminUser();
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListEmissionFactorGroups(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListEmissionFactorGroups(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new EmissionFactorGroup(EmissionFactorGroupId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): EmissionFactorGroupAuthorizer
    {
        return new EmissionFactorGroupAuthorizer();
    }
}
