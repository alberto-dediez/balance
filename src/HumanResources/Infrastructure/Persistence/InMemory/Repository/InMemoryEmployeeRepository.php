<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Persistence\InMemory\Repository;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryEmployeeRepository implements EmployeeRepository
{
    /** @param array<string, Employee> $employees */
    public function __construct(
        private array $employees = [],
    ) {}

    public function add(Employee $employee): void
    {
        if (isset($this->employees[(string) $employee->id()])) {
            return;
        }

        $this->employees[(string) $employee->id()] = $employee;
    }

    public function remove(Employee $employee): void
    {
        if (!isset($this->employees[(string) $employee->id()])) {
            return;
        }

        unset($this->employees[(string) $employee->id()]);
    }

    public function find(EmployeeId $employeeId): ?Employee
    {
        return (isset($this->employees[(string) $employeeId]) && false == $this->employees[(string) $employeeId]->isRemoved())
            ? $this->employees[(string) $employeeId]
            : null
        ;
    }

    /** @return array<int, Employee> */
    public function findByFilter(EmployeeFilter $filter): array
    {
        $results = [];

        if (0 === count($this->employees)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->employees as $employee) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $employee->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                !empty($filter->email)
                && !str_contains(strtolower((string) $employee->email()), strtolower($filter->email))
            ) {
                continue;
            }

            if (null !== $filter->accountId && !$filter->accountId->equals($employee->accountId())) {
                continue;
            }

            if (true == $employee->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $employee;
        }

        return $results;
    }

    public function totalResultsByFilter(EmployeeFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
