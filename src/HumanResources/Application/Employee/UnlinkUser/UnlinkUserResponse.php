<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\UnlinkUser;

use App\HumanResources\Domain\Model\Employee\EmployeeId;

class UnlinkUserResponse
{
    public function __construct(
        public EmployeeId $employeeId,
    ) {}
}
