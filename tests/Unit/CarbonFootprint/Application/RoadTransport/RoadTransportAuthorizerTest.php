<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RoadTransportAuthorizerTest extends TestCase
{
    public function testAdminCanListRoadTransports(): void
    {
        $user = UserMother::adminUser();
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterRoadTransports(): void
    {
        $user = UserMother::adminUser();
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditRoadTransport(): void
    {
        $user = UserMother::adminUser();
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveRoadTransport(): void
    {
        $user = UserMother::adminUser();
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListRoadTransports(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListRoadTransports(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new RoadTransport(RoadTransportId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): RoadTransportAuthorizer
    {
        return new RoadTransportAuthorizer();
    }
}
