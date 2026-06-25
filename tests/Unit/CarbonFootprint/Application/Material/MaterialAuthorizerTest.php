<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialAuthorizerTest extends TestCase
{
    public function testAdminCanListMaterials(): void
    {
        $user = UserMother::adminUser();
        $subject = new Material(MaterialId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterMaterials(): void
    {
        $user = UserMother::adminUser();
        $subject = new Material(MaterialId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditMaterial(): void
    {
        $user = UserMother::adminUser();
        $subject = new Material(MaterialId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveMaterial(): void
    {
        $user = UserMother::adminUser();
        $subject = new Material(MaterialId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListMaterials(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Material(MaterialId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListMaterials(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Material(MaterialId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): MaterialAuthorizer
    {
        return new MaterialAuthorizer();
    }
}
