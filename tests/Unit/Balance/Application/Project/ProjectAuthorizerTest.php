<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
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

    public function testOwnerCannotListNorViewProjectsWithoutCompensationFeature(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $subject = new Project(ProjectId::generate());
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertFalse($sut->canDoThat($subject, Action::List, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
    }

    public function testAdminCanListAndEditProjectsWithCompensationFeatureDisabled(): void
    {
        $accountId = AccountId::generate();
        $subject = new Project(ProjectId::generate());
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, UserMother::adminUser()));
    }

    private function createSUT(): ProjectAuthorizer
    {
        return new ProjectAuthorizer();
    }

    private function createSUTWithFeatures(AccountId $accountId, AccountFeatures $features): ProjectAuthorizer
    {
        return new ProjectAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($accountId),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($accountId, $features),
        );
    }
}
