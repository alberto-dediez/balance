<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class WasteAuthorizerTest extends TestCase
{
    public function testAdminCanListWastes(): void
    {
        $user = UserMother::adminUser();
        $subject = new Waste(WasteId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterWastes(): void
    {
        $user = UserMother::adminUser();
        $subject = new Waste(WasteId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditWaste(): void
    {
        $user = UserMother::adminUser();
        $subject = new Waste(WasteId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveWaste(): void
    {
        $user = UserMother::adminUser();
        $subject = new Waste(WasteId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListWastes(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Waste(WasteId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListWastes(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Waste(WasteId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): WasteAuthorizer
    {
        return new WasteAuthorizer();
    }
}
