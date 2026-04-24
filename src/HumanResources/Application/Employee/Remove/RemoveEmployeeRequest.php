<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Remove;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\Command;

class RemoveEmployeeRequest implements Command
{
    public function __construct(public EmployeeId $employeeId) {}
}
