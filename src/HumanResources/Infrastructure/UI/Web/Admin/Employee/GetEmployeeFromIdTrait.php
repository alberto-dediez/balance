<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\UI\Web\Admin\Employee;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;

trait GetEmployeeFromIdTrait
{
    private function getEmployeeFromId(string $employeeId, EmployeeRepository $repository): Employee
    {
        $employee = $repository->find(new EmployeeId($employeeId));

        if (null == $employee) {
            throw $this->createNotFoundException('Employee not found');
        }

        return $employee;
    }
}
