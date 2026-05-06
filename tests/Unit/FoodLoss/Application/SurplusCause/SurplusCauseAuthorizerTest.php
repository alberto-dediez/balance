<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\SurplusCause;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SurplusCauseAuthorizerTest extends TestCase
{
    public function testAdminCanListSurplusCauses(): void
    {
        $user = UserMother::adminUser();
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterSurplusCauses(): void
    {
        $user = UserMother::adminUser();
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditSurplusCause(): void
    {
        $user = UserMother::adminUser();
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveSurplusCause(): void
    {
        $user = UserMother::adminUser();
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListSurplusCauses(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListSurplusCauses(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new SurplusCause(SurplusCauseId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): SurplusCauseAuthorizer
    {
        return new SurplusCauseAuthorizer();
    }
}
