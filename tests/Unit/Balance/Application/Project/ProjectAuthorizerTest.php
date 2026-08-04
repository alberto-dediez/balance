<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProjectAuthorizerTest extends TestCase
{
    public function testAdminCanListProjects(): void
    {
        $user = UserMother::adminUser();
        $subject = new Project(ProjectId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterProjects(): void
    {
        $user = UserMother::adminUser();
        $subject = new Project(ProjectId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditProject(): void
    {
        $user = UserMother::adminUser();
        $subject = new Project(ProjectId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveProject(): void
    {
        $user = UserMother::adminUser();
        $subject = new Project(ProjectId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCanListAndViewProjects(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Project(ProjectId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCannotRegisterOrEditOrRemoveProjects(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Project(ProjectId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserCannotListProjects(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Project(ProjectId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): ProjectAuthorizer
    {
        return new ProjectAuthorizer();
    }
}
