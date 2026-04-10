<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingSession;

use App\FoodLoss\Application\TrainingSession\TrainingSessionAuthorizer;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class TrainingSessionAuthorizerTest extends TestCase
{
    public function testOwnerCanListTrainingSessionsFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCanListTrainingSessionsFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListTrainingSessionsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListTrainingSessionsFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterTrainingSession(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testUserCannotRegisterTrainingSession(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditTrainingSessionFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditTrainingSessionFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserCannotEditTrainingSession(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveTrainingSessionFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = TrainingSessionMother::from(title: 'Test', id: (string) TrainingSessionId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testAdminCannotListTrainingSessions(): void
    {
        $user = UserMother::adminUser();
        $subject = new TrainingSession(TrainingSessionId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): TrainingSessionAuthorizer
    {
        return new TrainingSessionAuthorizer();
    }
}
