<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Remove;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveEmployeeResponse implements CommandResponse
{
    public function __construct(public EmployeeId $employeeId) {}
}
