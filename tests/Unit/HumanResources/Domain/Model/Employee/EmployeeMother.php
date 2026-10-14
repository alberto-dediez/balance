<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class EmployeeMother
{
    public static function registeredEmployee(): Employee
    {
        return self::fromNameAndEmail('John Doe', 'john@example.com');
    }

    public static function fromNameAndEmail(string $name, string $email): Employee
    {
        return self::from(
            name: $name,
            email: $email,
            id: (string) EmployeeId::generate(),
            accountId: null,
        );
    }

    public static function fromNameEmailAndId(string $name, string $email, string $id): Employee
    {
        return self::from(
            name: $name,
            email: $email,
            id: $id,
            accountId: null,
        );
    }

    public static function from(
        string $name,
        string $email,
        string $id,
        ?string $accountId,
        ?string $phone = null,
    ): Employee {
        $employee = new Employee(
            id: new EmployeeId($id),
            accountId: AccountId::generate(),
        );
        $employee->register(
            name: $name,
            email: $email,
            phone: $phone,
            address: null,
            accountId: null === $accountId ? AccountId::generate() : new AccountId($accountId),
            userId: null,
        );

        return $employee;
    }

    public static function withUserId(string $id, string $userId, string $accountId = 'account-id'): Employee
    {
        $employee = new Employee(
            id: new EmployeeId($id),
            accountId: new AccountId($accountId),
        );
        $employee->register(
            name: 'Employee ' . $id,
            email: 'employee' . $id . '@example.com',
            phone: null,
            address: null,
            accountId: new AccountId($accountId),
            userId: new UserId($userId),
        );

        return $employee;
    }

    public static function forAccount(string $accountId): Employee
    {
        return self::from(
            name: 'Employee-' . rand(100, 999),
            email: 'employee' . rand(100, 999) . '@example.com',
            id: (string) EmployeeId::generate(),
            accountId: $accountId,
        );
    }

    public static function random(): Employee
    {
        return self::from(
            name: 'Employee-' . rand(100, 999),
            email: 'employee' . rand(100, 999) . '@example.com',
            id: (string) EmployeeId::generate(),
            accountId: null,
        );
    }
}
