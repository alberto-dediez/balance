<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MobilityAuthorizerTest extends TestCase
{
    public function testAdminCanListMobilities(): void
    {
        $user = UserMother::adminUser();
        $subject = new Mobility(MobilityId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterMobilities(): void
    {
        $user = UserMother::adminUser();
        $subject = new Mobility(MobilityId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditMobility(): void
    {
        $user = UserMother::adminUser();
        $subject = new Mobility(MobilityId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveMobility(): void
    {
        $user = UserMother::adminUser();
        $subject = new Mobility(MobilityId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListMobilities(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Mobility(MobilityId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListMobilities(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Mobility(MobilityId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): MobilityAuthorizer
    {
        return new MobilityAuthorizer();
    }
}
