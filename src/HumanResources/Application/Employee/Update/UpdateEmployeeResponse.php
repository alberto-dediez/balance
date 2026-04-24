<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Update;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateEmployeeResponse implements CommandResponse
{
    public function __construct(
        public EmployeeId $employeeId,
        public string $name,
        public string $email,
    ) {}
}
