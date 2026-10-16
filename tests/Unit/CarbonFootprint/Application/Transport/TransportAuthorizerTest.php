<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class TransportAuthorizerTest extends TestCase
{
    public function testAdminCanListTransports(): void
    {
        $user = UserMother::adminUser();
        $subject = new Transport(TransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterTransports(): void
    {
        $user = UserMother::adminUser();
        $subject = new Transport(TransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditTransport(): void
    {
        $user = UserMother::adminUser();
        $subject = new Transport(TransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveTransport(): void
    {
        $user = UserMother::adminUser();
        $subject = new Transport(TransportId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListTransports(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Transport(TransportId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListTransports(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Transport(TransportId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): TransportAuthorizer
    {
        return new TransportAuthorizer();
    }
}
