<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListEmployeeProtocolAccessHistory;

use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\HumanResources\Domain\Model\Employee\Employee;

class ListEmployeeProtocolAccessHistoryResponse
{
    /** @param array<ProtocolAccess> $accesses */
    public function __construct(
        public Employee $employee,
        public array $accesses = [],
    ) {}
}
