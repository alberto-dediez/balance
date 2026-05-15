<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class TrainingAttendanceAuthorizerTest extends TestCase
{
    public function testOwnerCanListAttendancesFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('account-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListAttendancesFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListAttendances(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('account-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotListAttendances(): void
    {
        $user = UserMother::adminUser();
        $subject = new TrainingAttendance(TrainingAttendanceId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterAttendancesFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('account-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCannotRegisterAttendancesFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanRemoveAttendancesFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('account-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotRemoveAttendancesFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('other-account');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserCannotRemoveAttendances(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-test'));
        $subject = TrainingAttendanceMother::forAccount('account-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): TrainingAttendanceAuthorizer
    {
        return new TrainingAttendanceAuthorizer();
    }
}
