<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\LinkUser;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\User\UserId;

class LinkUserRequest
{
    public function __construct(
        public EmployeeId $employeeId,
        public UserId $userId,
    ) {}
}
