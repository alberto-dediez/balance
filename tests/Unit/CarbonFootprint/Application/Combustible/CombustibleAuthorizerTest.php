<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CombustibleAuthorizerTest extends TestCase
{
    public function testAdminCanListCombustibles(): void
    {
        $user = UserMother::adminUser();
        $subject = new Combustible(CombustibleId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterCombustibles(): void
    {
        $user = UserMother::adminUser();
        $subject = new Combustible(CombustibleId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditCombustible(): void
    {
        $user = UserMother::adminUser();
        $subject = new Combustible(CombustibleId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveCombustible(): void
    {
        $user = UserMother::adminUser();
        $subject = new Combustible(CombustibleId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListCombustibles(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Combustible(CombustibleId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListCombustibles(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Combustible(CombustibleId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): CombustibleAuthorizer
    {
        return new CombustibleAuthorizer();
    }
}
