<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Register;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEmployeeResponse implements CommandResponse
{
    public function __construct(
        public EmployeeId $employeeId,
        public string $name,
        public string $email,
    ) {}
}
