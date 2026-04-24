<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

interface EmployeeRepository
{
    public function add(Employee $employee): void;

    public function remove(Employee $employee): void;

    public function find(EmployeeId $employeeId): ?Employee;

    /** @return array<Employee> */
    public function findByFilter(EmployeeFilter $filter): array;

    public function totalResultsByFilter(EmployeeFilter $filter): int;
}
