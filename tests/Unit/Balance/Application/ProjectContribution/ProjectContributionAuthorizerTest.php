<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProjectContributionAuthorizerTest extends TestCase
{
    public function testAdminCanListViewAndEditButNeverRegisterOrRemove(): void
    {
        $user = UserMother::adminUser();
        $subject = $this->subject(AccountId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerReadsTheContributionsOfOwnAccountOnly(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject($accountId), Action::Edit, $user));
    }

    public function testOnlyTheOwnerOfTheAccountCanRegisterAContribution(): void
    {
        $accountId = AccountId::generate();
        $subject = $this->subject($accountId);

        $this->assertTrue($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        ));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, AccountId::generate()),
        ));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId),
        ));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, UserMother::adminUser()));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRole('super', Role::SuperAdmin),
        ));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(null), Action::List, $user));
    }

    public function testUserWithoutAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::List, $user));
    }

    public function testSupportsOnlyProjectContributions(): void
    {
        $this->assertTrue($this->createSUT()->supports($this->subject(null)));
        $this->assertFalse($this->createSUT()->supports(ProjectContributionId::generate()));
    }

    private function subject(?AccountId $accountId): ProjectContribution
    {
        return new ProjectContribution(id: ProjectContributionId::generate(), accountId: $accountId);
    }

    private function createSUT(): ProjectContributionAuthorizer
    {
        return new ProjectContributionAuthorizer();
    }
}
