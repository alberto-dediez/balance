<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\Security\Domain\Model\User\UserId;

interface EmployeeRepository
{
    public function add(Employee $employee): void;

    public function remove(Employee $employee): void;

    public function find(EmployeeId $employeeId): ?Employee;

    public function findByUserId(UserId $userId): ?Employee;

    /** @return array<Employee> */
    public function findByFilter(EmployeeFilter $filter): array;

    public function totalResultsByFilter(EmployeeFilter $filter): int;
}
