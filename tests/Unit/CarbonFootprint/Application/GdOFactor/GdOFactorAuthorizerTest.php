<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GdOFactorAuthorizerTest extends TestCase
{
    public function testAdminCanListGdOFactors(): void
    {
        $user = UserMother::adminUser();
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterGdOFactors(): void
    {
        $user = UserMother::adminUser();
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditGdOFactor(): void
    {
        $user = UserMother::adminUser();
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveGdOFactor(): void
    {
        $user = UserMother::adminUser();
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListGdOFactors(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListGdOFactors(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new GdOFactor(GdOFactorId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): GdOFactorAuthorizer
    {
        return new GdOFactorAuthorizer();
    }
}
