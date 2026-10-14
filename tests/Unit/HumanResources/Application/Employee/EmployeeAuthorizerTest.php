<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EmployeeAuthorizerTest extends TestCase
{
    public function testAdminCanListEmployees(): void
    {
        $user = UserMother::adminUser();
        $subject = new Employee(id: EmployeeId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterEmployees(): void
    {
        $user = UserMother::adminUser();
        $subject = new Employee(id: EmployeeId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditEmployees(): void
    {
        $user = UserMother::adminUser();
        $subject = EmployeeMother::registeredEmployee();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveEmployees(): void
    {
        $user = UserMother::adminUser();
        $subject = EmployeeMother::registeredEmployee();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCanListEmployeesFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'account-id-test',
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterEmployeesForSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'account-id-test',
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditEmployeeFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'account-id-test',
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditEmployeeFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'other-account-id',
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveEmployeeFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'account-id-test',
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotRemoveEmployeeFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EmployeeMother::from(
            name: 'Test',
            email: 'test@example.com',
            id: (string) EmployeeId::generate(),
            accountId: 'other-account-id',
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Employee(id: EmployeeId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListEmployees(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Employee(id: EmployeeId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): EmployeeAuthorizer
    {
        return new EmployeeAuthorizer();
    }
}
